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
|   > $Date: 2007-10-17 16:29:37 -0400 (Wed, 17 Oct 2007) $
|   > $Revision: 1133 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Pay Subscriptions Manager
|   > Module written by Matt Mecham
|   > Date started: Friday 1st April 2005 (14:05)
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

//---------------------------------------
// Carry on!
//---------------------------------------

class ad_paysubscriptions
{

	# Global
	var $ipsclass;
	var $html;
	var $gateway;
	var $email;
	
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
	var $perm_child = "msubs";
	
	/*-------------------------------------------------------------------------*/
	// IPB auto_run
	/*-------------------------------------------------------------------------*/
	
	function auto_run()
	{
		//-----------------------------------------
		// Load skin
		//-----------------------------------------
		
		$this->html = $this->ipsclass->acp_load_template('cp_skin_paysubs');
		
		//--------------------------------------------
    	// Get the sync module
		//--------------------------------------------
		
		if ( USE_MODULES == 1 )
		{
			require ROOT_PATH."modules/ipb_member_sync.php";
			
			$this->modules = new ipb_member_sync();
		}
		
		//-----------------------------------------
		// GET EMAIL CLASS
		//-----------------------------------------
		
		require_once( ROOT_PATH."sources/classes/class_email.php" );
		$this->email = new emailer( ROOT_PATH );
        $this->email->ipsclass =& $this->ipsclass;
        $this->email->email_init();
        
		define( 'GW_CORE_INIT', TRUE );
		
		//--------------------------------------------
		// Load extra db cache file
		//--------------------------------------------
		
		$this->ipsclass->DB->load_cache_file( ROOT_PATH.'sources/sql/'.SQL_DRIVER.'_subsm_queries.php', 'sql_subsm_queries' );
		
		$this->ipsclass->admin->page_title  = "IPB Subscriptions Manager";
		$this->ipsclass->admin->page_detail = "You can set up and manage your member's paid subscriptions in this section.";
		
		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code, 'IPB Subscription Manager Home' );
		
		//-----------------------------------------
		// Do what?
		//-----------------------------------------
		
		switch($this->ipsclass->input['code'])
		{
			//-----------------------------------------
			case 'install-index':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':install' );
				$this->install_index();
				break;
			case 'install-gateway':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':install' );
				$this->install_gateway();
				break;
			case 'edit_package_gateway_info':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->edit_package_gateway_info();
				break;
			case 'edit_package_complete':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->edit_package_complete();
				break;
			//-----------------------------------------
			case 'removepackage':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':remove' );
				$this->remove_package();
				break;
			case 'remove_package_complete':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':remove' );
				$this->remove_package_complete();
				break;
			//-----------------------------------------
			case 'removemembers':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':remove' );
				$this->remove_members();
				break;
			case 'remove_members_complete':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':remove' );
				$this->remove_members_complete();
				break;
			//-----------------------------------------
			case 'addpackage':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->alter_package_form('add');
				break;
				
			case 'doaddpackage':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->do_add_package();
				break;
			//-----------------------------------------
			case 'editpackage':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->alter_package_form('edit');
				break;
				
			case 'doeditpackage':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->do_edit_package();
				break;
			//-----------------------------------------
			case 'editmethod':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->edit_method();
				break;
				
			case 'edit_method_complete':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->edit_method_complete();
				break;
			//-----------------------------------------
			case 'find_transactions':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':search' );
				$this->find_transactions();
				break;
			case 'find_logs':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':search' );
				$this->find_logs();
				break;
			case 'find_logs_view_entry':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':search' );
				$this->find_logs_view_entry();
				break;
			//-----------------------------------------
			case 'domodifytrans':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->do_modify_trans();
				break;
				
			case 'dotransdelete':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':remove' );
				$this->do_delete_trans();
				break;
			
			//-----------------------------------------
			case 'edittransaction':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->edit_transaction('edit');
				break;
				
			case 'addtransaction':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->edit_transaction('add');
				break;
			
			case 'doedittransaction':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->save_transaction('edit');
				break;
				
			case 'doaddtransaction':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->save_transaction('add');
				break;
				
			//-----------------------------------------
			
			case 'currency':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->currency_index();
				break;
			case 'editcurrency':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->currency_edit();
				break;
			case 'deletecurrency':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':remove' );
				$this->currency_delete();
				break;
			
			//-----------------------------------------
			// View...
			//-----------------------------------------
			
			case 'index-gateways':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->paysubs_index_gateways();
				break;
			case 'index-packages':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->paysubs_index_packages();
				break;
			case 'index-tools':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->paysubs_index_tools();
				break;
			
			default:
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->paysubs_index_gateways();
				break;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Install INDEX
	/*-------------------------------------------------------------------------*/
	
	function install_gateway()
	{
		//-------------------------------------------
		// INIT
		//-------------------------------------------
		
		$gateway = trim( $this->ipsclass->input['name'] );
		$gateway = preg_replace( '#\.{1,}#s', '.', $gateway );
		
		//-------------------------------------------
		// Check...
		//-------------------------------------------
		
		if ( ! $gateway )
		{
			$this->ipsclass->main_msg = "No gateway specified";
			$this->install_index();
		}
		
		$test = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'submethod_name', 'from' => 'subscription_methods', 'where' => "submethod_name='{$gateway}'"  ) );
		
		if ( $test['submethod_name'] )
		{
			$this->ipsclass->main_msg = "Gateway already installed";
			$this->install_index();
		}
		
		//-------------------------------------------
		// Load it
		//-------------------------------------------
		
		require_once( ROOT_PATH . 'sources/classes/paymentgateways/class_gw_core.php' );
		require_once( ROOT_PATH . 'sources/classes/paymentgateways/class_gw_'.$gateway.'.php' );
			
		$this->gateway           =  new class_gw_module();
		$this->gateway->ipsclass =& $this->ipsclass;
		
		//-------------------------------------------
		// Get info...
		//-------------------------------------------
		
		$this->gateway->install_gateway();
		
		//-------------------------------------------
		// Install language
		//-------------------------------------------
		
		if ( is_array( $this->gateway->install_lang ) and count ( $this->gateway->install_lang ) )
		{
			require( ROOT_PATH.'sources/api/api_language.php' );
			$api           =  new api_language();
			$api->ipsclass =& $this->ipsclass;
			$api->api_init();

			$api->lang_add_strings( $this->gateway->install_lang, 'lang_subscriptions' );
		}
		
		//-------------------------------------------
		// Install DB
		//-------------------------------------------
		
		if ( is_array( $this->gateway->db_info ) )
		{
			$this->ipsclass->DB->do_insert( 'subscription_methods', array( 'submethod_title'         => $this->gateway->db_info['human_title'],
																		   'submethod_desc'          => $this->gateway->db_info['human_desc'],
																		   'submethod_name'          => $this->gateway->db_info['module_name'],
																		   'submethod_is_cc'         => intval($this->gateway->db_info['allow_creditcards']),
																		   'submethod_is_auto'       => intval($this->gateway->db_info['allow_auto_validate']),
																		   'submethod_use_currency'  => $this->gateway->db_info['default_currency'] ) );
		}
		
		//-------------------------------------------
		// Done...
		//-------------------------------------------
		
		$this->ipsclass->main_msg = "Gateway installed";
		$this->install_index();
	}
	
	/*-------------------------------------------------------------------------*/
	// Install INDEX
	/*-------------------------------------------------------------------------*/
	
	function install_index()
	{
		//-------------------------------------------
		// INIT
		//-------------------------------------------
		
		$methods      = array();
		$dir_contents = array();
		$dir_path     = ROOT_PATH.'sources/classes/paymentgateways';
		$content      = "";
		
		//-------------------------------------------
		// Get all current methods
		//-------------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select' => 'submethod_name', 'from' => 'subscription_methods' ) );
		$this->ipsclass->DB->exec_query();
		
		while( $r = $this->ipsclass->DB->fetch_row() )
		{
			$methods[ strtolower($r['submethod_name']) ] = 1;
		}
		
		//-------------------------------------------
		// Get contents of dir
		//-------------------------------------------
		
		$handle = opendir( $dir_path );
			
		while ( ( $file = readdir($handle) ) !== FALSE )
		{
			if ( ($file != ".") && ($file != "..") )
			{
				if ( preg_match( "#class_gw_(.+?)\.php$#", $file, $match ) )
				{
					if ( $match[1] == 'core' )
					{
						continue;
					}
					
					$dir_contents[ strtolower($match[1]) ] = $file;
				}
			}
		}
		
		closedir($handle);
		
		//-------------------------------------------
		// Loop and list
		//-------------------------------------------
		
		foreach( $dir_contents as $gateway => $filename )
		{
			//-------------------------------------------
			// Already installed?
			//-------------------------------------------
			
			if ( isset($methods[ $gateway ]) AND $methods[ $gateway ] )
			{
				$installed = 1;
			}
			else
			{
				$installed = 0;
			}
			
			$content .= $this->html->gateway_install_row( $gateway, $installed );
		}
		
		$this->ipsclass->html .= $this->html->gateway_install_wrapper( $content );
		
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// Currency Overview - Yeah
	/*-------------------------------------------------------------------------*/
	
	function currency_index($message="")
	{
		$default = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'subscription_currency', 'where' => 'subcurrency_default=1' ) );
		
		 $this->ipsclass->DB->fetch_row();
		
		//-------------------------------------------
		// Message in a bottle?
		//-------------------------------------------
		
		if ( $message != "" )
		{
			$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "100%" );
		
			$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Message" );
			
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( $message )  );
			
			$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		}
		
		$this->ipsclass->admin->page_detail .= "<br /><br /><strong>Currency Information</strong><br />The currency you set as default becomes the currency the subscriptions are based in. For example, if you chose USD as default, entering a value of 1.00 for a subscription package means that the subscription
							    package costs 1.00 USD. If you choose another default you may want to edit all the subscription values as they will become incorrect. If you choose another default, you will also want to edit the exchange values.<br /><br />For up-to-date currency conversion, visit <a href='http://www.xe.com' target='_blank'>XE.com</a>.";
		$this->ipsclass->admin->nav[] = array( '', 'Manage Currencies' );
		
		//-------------------------------------------
		// Quick Jump Table
		//-------------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'    , 'editcurrency'),
																			 2 => array( 'act'     , 'msubs'    ),
																			 4 => array( 'section', $this->ipsclass->section_code ),
																	)  );
									     		   
		$this->ipsclass->adskin->td_header[] = array( "Code"         , "10%" );
		$this->ipsclass->adskin->td_header[] = array( "Description"  , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "Conv. Rate"   , "30%" );
		$this->ipsclass->adskin->td_header[] = array( "Default?"     , "10%" );
		$this->ipsclass->adskin->td_header[] = array( "Delete"       , "10%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Available Currencies");
		
		$not_in = ' USD GBP EUR CAD ';
		
		$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'subscription_currency' ) );
		$this->ipsclass->DB->exec_query();
		
		while( $c = $this->ipsclass->DB->fetch_row() )
		{
			$checked = $c['subcurrency_default'] == 1 ? " checked='checked'" : "";
			
			$delete_link = "<i>Can't Delete</i>";
			
			if ( ! strstr( $not_in, $c['subcurrency_code'] ) )
			{
				if ( $default['subcurrency_code'] != $c['subcurrency_code'] )
				{
					$delete_link = "<a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=deletecurrency&currency=".$c['subcurrency_code']."'>Delete</a>";
				}
			}
			
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>{$c['subcurrency_code']}</b>" ,
																				 $this->ipsclass->adskin->form_input( 'desc_'.$c['subcurrency_code'], $c['subcurrency_desc'] ),
																				 "1 X ".$default['subcurrency_code']." = ".$this->ipsclass->adskin->form_simple_input( 'exchange_'.$c['subcurrency_code'], $c['subcurrency_exchange'], 12 )." ".$c['subcurrency_code'],
																				 "<center><input type='radio' name='default' value='{$c['subcurrency_code']}' $checked /></center>",
																				 "<center>{$delete_link}</center>"
																		)      );
										 
		}
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic( 'Add a new currency', 'left', 'tablesubheader' );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( $this->ipsclass->adskin->form_simple_input( 'add_code', "", 3 ) ,
																			 $this->ipsclass->adskin->form_input( 'add_desc' ),
																			 "1 X ".$default['subcurrency_code']." = ".$this->ipsclass->adskin->form_simple_input( 'add_exchange', "", 12 )." <i>new currency</i>",
																			 "&nbsp;",
																			 "&nbsp;"
																	)      );
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form( "Save Settings" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// Currency Overview - EDIT
	/*-------------------------------------------------------------------------*/
	
	function currency_edit()
	{
		$currency = array();
		
		$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'subscription_currency' ) );
		$this->ipsclass->DB->exec_query();
				
		while ( $c = $this->ipsclass->DB->fetch_row() )
		{
			$currency[ $c['subcurrency_code'] ] = $c;
		}
		
		foreach ( $currency as $code => $data )
		{
			if ( $this->ipsclass->input[ 'desc_'.$code ] AND $this->ipsclass->input[ 'exchange_'.$code ] )
			{
				$this->ipsclass->DB->do_update( 'subscription_currency', array( 'subcurrency_desc' => $this->ipsclass->input[ 'desc_'.$code ],
																				'subcurrency_exchange' => $this->ipsclass->input[ 'exchange_'.$code ] ), "subcurrency_code='{$code}'" );
			}
		}
		
		// Sort out default...
		
		$this->ipsclass->DB->do_update( 'subscription_currency', array( 'subcurrency_default' => 0 ) );
		$this->ipsclass->DB->do_update( 'subscription_currency', array( 'subcurrency_default' => 1 ), "subcurrency_code='{$this->ipsclass->input['default']}'" );
				
		// Addition?
		
		if ( $this->ipsclass->input['add_code'] AND $this->ipsclass->input['add_desc'] AND $this->ipsclass->input['add_exchange'] )
		{
			$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'subscription_currency', 'where' => "subcurrency_code='{$this->ipsclass->input['add_code']}'" ) );
			$this->ipsclass->DB->exec_query();
			
			if ( $t = $this->ipsclass->DB->fetch_row() )
			{
				$this->currency_index("You cannot use currency code '{$this->ipsclass->input['add_code']}' as it already exists.");
			}
			
			$this->ipsclass->DB->do_insert( 'subscription_currency', array( 'subcurrency_code'     => $this->ipsclass->input['add_code'],
																			'subcurrency_desc'     => $this->ipsclass->input['add_desc'],
																			'subcurrency_exchange' => $this->ipsclass->input['add_exchange'] ) );
		}
		
		$this->ipsclass->admin->save_log("Currency: Edited");
		
		$this->currency_index("Currency settings updated");
	}
	
	/*-------------------------------------------------------------------------*/
	// Currency Overview - DELETE
	/*-------------------------------------------------------------------------*/
	
	function currency_delete()
	{
		if ( $this->ipsclass->input['currency'] == "" )
		{
			$this->ipsclass->admin->error("Couldn't find a currency to delete.");
		}
		
		$this->ipsclass->DB->do_delete( 'subscription_currency', "subcurrency_code='{$this->ipsclass->input['currency']}'" );
		
		$this->ipsclass->admin->save_log("Currency '{$this->ipsclass->input['currency']}' Deleted");
		
		$this->currency_index("Currency '{$this->ipsclass->input['currency']}' Deleted");
	}
	
	/*-------------------------------------------------------------------------*/
	// Complete pkg / gateway edit
	/*-------------------------------------------------------------------------*/
	
	function edit_package_complete()
	{
		//-------------------------------------------
		// INIT
		//-------------------------------------------
		
		$method_id = intval($this->ipsclass->input['method']);
		$subpkg_id = intval($this->ipsclass->input['sub']);
		
		$this_pkg  = array();
		$this_mtd  = array();
		
		//-------------------------------------------
		// Check
		//-------------------------------------------
		
		if ( $method_id < 1 )
		{
			$this->ipsclass->admin->error("No method_id passed");
		}
		
		if ( $subpkg_id < 1 )
		{
			$this->ipsclass->admin->error("No subpkg_id passed");
		}
		
		//-------------------------------------------
		// Check...
		//-------------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'subscriptions', 'where' => "sub_id={$subpkg_id}" ) );
		$this->ipsclass->DB->exec_query();		
		
		if ( ! $row = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->admin->error("Could locate a sub pkg with the id of {$this->ipsclass->input['id']}");
		}
		
		$newbie =  array (
						   'subextra_sub_id'      => $subpkg_id,
						   'subextra_method_id'   => $method_id,
						   'subextra_product_id'  => $this->ipsclass->input['subextra_product_id'],
						   'subextra_can_upgrade' => intval($this->ipsclass->input['subextra_can_upgrade']),
						   'subextra_recurring'   => intval($this->ipsclass->input['subextra_recurring']),
				         );
											  
		foreach( array( 1,2,3,4,5 ) as $id )
		{
			if ( isset($_POST['subextra_custom_'.$id]) )
			{
				$newbie[ 'subextra_custom_'.$id ] = $this->ipsclass->txt_safeslashes( $_POST['subextra_custom_'.$id] );
			}
		}
		
		//-------------------------------------------
		// Do we 'ave a row already my old bean?
		//-------------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select' => 'subextra_id', 'from' => 'subscription_extra', 'where' => "subextra_sub_id={$subpkg_id} AND subextra_method_id={$method_id}" ) );
		$this->ipsclass->DB->exec_query();	
		
		if ( $this->ipsclass->DB->get_num_rows() )
		{
			//-------------------------------------------
			// Already exists, update!
			//-------------------------------------------
											  
			$this->ipsclass->DB->do_update( 'subscription_extra', $newbie, "subextra_sub_id={$subpkg_id} AND subextra_method_id={$method_id}" );
		}
		else
		{
			//-------------------------------------------
			// Doesn't exist, go add!
			//-------------------------------------------
			
			$this->ipsclass->DB->do_insert( 'subscription_extra', $newbie );
			
		}
		
		$this->ipsclass->admin->save_log("Payment specific information for gateway edited");
		
		$this->ipsclass->main_msg = "Settings Saved";
		$this->edit_package_gateway_info();
	}
	
	/*-------------------------------------------------------------------------*/
	// Edit/Add a package gateway specific ting man
	/*-------------------------------------------------------------------------*/
	
	function edit_package_gateway_info()
	{
		//-------------------------------------------
		// INIT
		//-------------------------------------------
		
		$method_id = intval($this->ipsclass->input['method']);
		$subpkg_id = intval($this->ipsclass->input['sub']);
		
		$this_pkg  = array();
		$this_mtd  = array();
		
		//-------------------------------------------
		// Check
		//-------------------------------------------
		
		if ( $method_id < 1 )
		{
			$this->ipsclass->admin->error("No method_id passed");
		}
		
		if ( $subpkg_id < 1 )
		{
			$this->ipsclass->admin->error("No subpkg_id passed");
		}
		
		//-------------------------------------------
		// Get packages and get methods
		//-------------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select' => 'sub_id, sub_title, sub_cost', 'from' => 'subscriptions', 'order' => 'sub_cost' ) );
		$this->ipsclass->DB->exec_query();	
				
		$packages = array();
		
		while ( $p = $this->ipsclass->DB->fetch_row() )
		{
			$packages[] = array( $p['sub_id'], $p['sub_title'] );
			
			if ( $p['sub_id'] == $subpkg_id )
			{
				$this_pkg = $p;
			}
		}
		
		$methods = array();
		
		$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'subscription_methods', 'order' => 'submethod_title' ) );
		$this->ipsclass->DB->exec_query();	
				
		while ( $m = $this->ipsclass->DB->fetch_row() )
		{
			$methods[] = array( $m['submethod_id'], $m['submethod_title'] );
			
			if ( $m['submethod_id'] == $method_id )
			{
				$this_mtd = $m;
			}
		}
		
		$row = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'subscription_extra', 'where' => "subextra_sub_id={$subpkg_id} AND subextra_method_id={$method_id}" ) );
		
		$this->ipsclass->admin->page_detail .= "<br /><br /><strong>Editing Gateway '{$this_mtd['submethod_title']}' specific information for package '{$this_pkg['sub_title']}'.</strong>";
		$this->ipsclass->admin->nav[] = array( '', 'Editing Gateway '.$this_mtd['submethod_title'].' Info for Package '.$this_pkg['sub_title'] );
		
		//-------------------------------------------
		// Quick Jump Table
		//-------------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'    , 'edit_package_gateway_info'),
														         2 => array( 'act'     , 'msubs'    ),
														         4 => array( 'section', $this->ipsclass->section_code ),
									    			    )  );
									     		   
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Quick Jump");
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Payment Gateway</b>" ,
												 				 $this->ipsclass->adskin->form_dropdown( 'method', $methods, $this_mtd['submethod_id'] )
										 				)      );
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Subscription Package</b>" ,
												 				 $this->ipsclass->adskin->form_dropdown( 'sub', $packages, $this_pkg['sub_id'] )
										 				)      );
												 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form( "Go!" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		//-------------------------------------------
		// Carry on!
		//-------------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'    , 'edit_package_complete'),
																			 2 => array( 'act'     , 'msubs'    ),
																			 3 => array( 'method'  , $method_id ),
																			 4 => array( 'sub'     , $subpkg_id ),
																			 5 => array( 'section', $this->ipsclass->section_code ),
																			 
																	)  );
									     		   
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "{$this_mtd['submethod_title']} -&gt; {$this_pkg['sub_title']}");
		
		//---------------------------------
		// Load the API...
		//---------------------------------
		
		$custom = array();
		
		if ( @file_exists( ROOT_PATH . 'sources/classes/paymentgateways/class_gw_'.$this_mtd['submethod_name'].'.php' ) )
		{
			require_once( ROOT_PATH . 'sources/classes/paymentgateways/class_gw_core.php' );
			require_once( ROOT_PATH . 'sources/classes/paymentgateways/class_gw_'.$this_mtd['submethod_name'].'.php' );
			
			$this->gateway = new class_gw_module();
			
			//----------------------------------
			// Sort out the custom method fields
			//----------------------------------
			
			$form = $this->gateway->acp_return_package_variables();
			
			foreach( $form as $name => $data )
			{
				if ( $data['used'] != 0 )
				{
					$custom[] = $this->ipsclass->adskin->add_td_row( array( "<b>{$data['formname']}</b><br />{$data['formextra']}</b>" ,
														  $this->ipsclass->adskin->form_input( $name, $row[ $name ] )
												 )      );
				}
			}
			
		}
		else
		{
			$this->ipsclass->admin->error("Could not locate the API in: ".ROOT_PATH . 'modules/subsmanager/api_'.$row['submethod_name'].'.php');
		}
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Subscription / Gateway Combo: Product ID</b><br />This is not required by all combinations" ,
												  							 $this->ipsclass->adskin->form_input("subextra_product_id", $row['subextra_product_id'] )
									     							 )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Can be recurringly billed for this package using this gateway?</b><br />This is not required by all combinations and will not count if the package never expires" ,
												  							 $this->gateway->can_do_recurring_billing == 0 ? "This gateway does not support recurring billing" : $this->ipsclass->adskin->form_yes_no("subextra_recurring", $row['subextra_recurring'] )
									     							 )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Can upgrade from this package using this gateway?</b><br />This is not required by all combinations" ,
												  							 $this->gateway->can_do_upgrades == 0 ? "This gateway does not support upgrading packages" : $this->ipsclass->adskin->form_yes_no("subextra_can_upgrade", $row['subextra_can_upgrade'] )
									     							 )      );
		
		if ( count( $custom ) > 0 )
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic( 'Gateway Specific Settings', 'left', 'catrow2' );
			
			$this->ipsclass->html .= implode( "\n", $custom );
		}
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form( "Save Settings" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// Remove Package: You can do iiiiit! I know.
	/*-------------------------------------------------------------------------*/
	
	function remove_package_complete()
	{
		$this->ipsclass->input['id'] = isset($this->ipsclass->input['id']) ? intval($this->ipsclass->input['id']) : 0;
		
		if ( ! $this->ipsclass->input['id'] )
		{
			$this->ipsclass->admin->error("Could locate a subscription package with the id of {$this->ipsclass->input['id']}");
		}
		
		$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'subscriptions', 'where' => "sub_id={$this->ipsclass->input['id']}" ) );
		$this->ipsclass->DB->exec_query();
				
		if ( ! $row = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->admin->error("Could locate a subscription package with the id of {$this->ipsclass->input['id']}");
		}
		
		$this->_unsub_members($this->ipsclass->input['id'], 'all', 'dead');
		
		$this->ipsclass->DB->do_delete( 'subscriptions', "sub_id={$this->ipsclass->input['id']}" );
		
		$this->ipsclass->admin->save_log("Subscription Package {$row['sub_title']} removed");
		
		$this->ipsclass->boink_it( $this->ipsclass->base_url."&{$this->ipsclass->form_code}" );
	}
	
	/*-------------------------------------------------------------------------*/
	// Remove Package: Step One
	/*-------------------------------------------------------------------------*/
	
	function remove_package()
	{
		$time = time();
		$this->ipsclass->input['id'] = isset($this->ipsclass->input['id']) ? intval($this->ipsclass->input['id']) : 0;
		
		if ( ! $this->ipsclass->input['id'] )
		{
			$this->ipsclass->admin->error("Could locate a subscription package with the id of {$this->ipsclass->input['id']}");
		}
		
		$row = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'COUNT(*) as total', 'from' => 'subscription_trans', 'where' => "subtrans_sub_id={$this->ipsclass->input['id']}" ) );
		
		$total = intval( $row['total'] );
		
		$sub = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'sub_title', 'from' => 'subscriptions', 'where' => "sub_id={$this->ipsclass->input['id']}" ) );
	
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , 'remove_package_complete'  ),
												  2 => array( 'act'   , 'msubs'            ),
												  3 => array( 'id'    , $this->ipsclass->input['id']          ),
												  4 => array( 'section', $this->ipsclass->section_code ),
									     )  );
									     
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "100%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Remove Package '{$sub['sub_title']}' Confirmation" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Members subscribed to {$sub['sub_title']}: $total</b><br /><br />Deleting this package will remove all subscribed members and return them back to their previous group. It will also mark all transactions currently subscribed to this package as 'dead'
												   							 Please note that if the group that they were in no longer exists, they will be moved into the default member group."
									     							 )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Remove Package");
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// Remove Members: You can do iiiiit!
	/*-------------------------------------------------------------------------*/
	
	function remove_members_complete()
	{
		//-------------------------------------------
		// INIT
		//-------------------------------------------
		
		$this->ipsclass->input['id'] = isset($this->ipsclass->input['id']) ? intval($this->ipsclass->input['id']) : 0;
		
		//-------------------------------------------
		// Check
		//-------------------------------------------
		
		if ( ! $this->ipsclass->input['id'] )
		{
			$this->ipsclass->admin->error("Could locate a subscription package with the id of {$this->ipsclass->input['id']}");
		}
		
		$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'subscriptions', 'where' => "sub_id={$this->ipsclass->input['id']}" ) );
		$this->ipsclass->DB->exec_query();
				
		if ( ! $row = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->admin->error("Could locate a subscription package with the id of {$this->ipsclass->input['id']}");
		}
		
		$this->_unsub_members($this->ipsclass->input['id'], $this->ipsclass->input['type']);
		
		$this->ipsclass->admin->save_log("Members unsubscribed from package {$row['sub_title']} using type {$this->ipsclass->input['type']}");
		
		$this->ipsclass->main_msg = "Members unsubscribed from package {$row['sub_title']} using type {$this->ipsclass->input['type']}";
		$this->paysubs_index_packages();
	}
	
	
	/*-------------------------------------------------------------------------*/
	// Remove Members: Step One
	/*-------------------------------------------------------------------------*/
	
	function remove_members()
	{
		//-------------------------------------------
		// INIT
		//-------------------------------------------
		
		$time                        = time();
		$this->ipsclass->input['id'] = isset($this->ipsclass->input['id']) ?  intval($this->ipsclass->input['id']) : 0;
		
		//-------------------------------------------
		// Check
		//-------------------------------------------
		
		if ( ! $this->ipsclass->input['id'] )
		{
			$this->ipsclass->admin->error("Could locate a subscription package with the id of {$this->ipsclass->input['id']}");
		}
		
		if ( $this->ipsclass->input['type'] != 'all' )
		{
			$query = "subtrans_end_date < $time AND subtrans_sub_id={$this->ipsclass->input['id']}";
		}
		else
		{
			$query = "subtrans_sub_id={$this->ipsclass->input['id']}";
		}
		
		$row = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'count(*) as total', 'from' => 'subscription_trans', 'where' => $query ) );
		
		$total = intval( $row['total'] );
		
		if ( $total < 1 )
		{
			$this->ipsclass->admin->error("There are no members to remove.");
		}
	
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , 'remove_members_complete'  ),
												  2 => array( 'act'   , 'msubs'            ),
												  3 => array( 'type'  , $this->ipsclass->input['type']        ),
												  4 => array( 'id'    , $this->ipsclass->input['id']          ),
												  5 => array( 'section', $this->ipsclass->section_code ),
									     )  );
									     
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "100%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Unsubscription Confirmation" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Members to unsubscribe: $total</b><br /><br />Unsubscribing members will mark their transaction as 'expired' and return them to the group they were in before they subscribed.
												   							 Please note that if the group that they were in no longer exists, they will be moved into the default member group."
									     							 )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Unsubscribe");
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// Subscription Package: Add Package
	/*-------------------------------------------------------------------------*/
	
	function do_add_package()
	{
		$this->ipsclass->input['sub_run_module'] = preg_replace( '#\.{1,}#s', '.', $this->ipsclass->input['sub_run_module'] );
				
		if ( $this->ipsclass->input['sub_title'] == "" )
		{
			$this->ipsclass->admin->error("You must enter a valid title for this subscription package");
		}
		
		if ( $this->ipsclass->input['sub_cost'] == "" )
		{
			$this->ipsclass->admin->error("You must enter a valid cost for this subscription package");
		}
		
		if ( $this->ipsclass->input['sub_noexpire'] )
		{
			$this->ipsclass->input['sub_unit']   = 'x';
			$this->ipsclass->input['sub_length'] = 0;
		}
		
		if ( $this->ipsclass->vars['admin_group'] == $this->ipsclass->input['sub_new_group'] )
		{
			if ( $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'] )
			{
				$this->ipsclass->input['sub_new_group'] = 0;
			}
		}		
		
		$this->ipsclass->DB->do_insert( "subscriptions", array (
														 'sub_title'          => str_replace( "'", "", str_replace( "&#39;", "'", $this->ipsclass->input['sub_title'])),
														 'sub_desc'           => $this->ipsclass->txt_safeslashes(trim($_POST['sub_desc'])),
														 'sub_new_group'      => intval($this->ipsclass->input['sub_new_group']),
														 'sub_length'         => $this->ipsclass->input['sub_length'],
														 'sub_unit'           => $this->ipsclass->input['sub_unit'],
														 'sub_cost'			  => $this->ipsclass->input['sub_cost'],
														 'sub_run_module'	  => $this->ipsclass->input['sub_run_module'],
											  ) 				);
		
		$this->ipsclass->admin->save_log("Subscription Package '{$this->ipsclass->input['sub_title']}' created");
		
		$this->ipsclass->main_msg = 'Subscription Package Added';
		$this->paysubs_index_packages();
	}
	
	/*-------------------------------------------------------------------------*/
	// Subscription Package: Complete Edit
	/*-------------------------------------------------------------------------*/
	
	function do_edit_package()
	{
		//-------------------------------------------
		// INIT
		//-------------------------------------------
		
		$this->ipsclass->input['id'] = isset($this->ipsclass->input['id']) ? intval($this->ipsclass->input['id']) : 0;
		$this->ipsclass->input['sub_run_module'] = preg_replace( '#\.{1,}#s', '.', $this->ipsclass->input['sub_run_module'] );
		
		if ( ! $this->ipsclass->input['id'] )
		{
			$this->ipsclass->admin->error("Could locate a payment gateway with the id of {$this->ipsclass->input['id']}");
		}
		
		$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'subscriptions', 'where' => "sub_id={$this->ipsclass->input['id']}" ) );
		$this->ipsclass->DB->exec_query();		
		
		if ( ! $row = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->admin->error("Could locate a payment gateway with the id of {$this->ipsclass->input['id']}");
		}
		
		if ( $this->ipsclass->input['sub_title'] == "" )
		{
			$this->ipsclass->admin->error("You must enter a valid title for this subscription package");
		}
		
		if ( $this->ipsclass->input['sub_cost'] == "" )
		{
			$this->ipsclass->admin->error("You must enter a valid cost for this subscription package");
		}
		
		if ( $this->ipsclass->input['sub_noexpire'] )
		{
			$this->ipsclass->input['sub_unit']   = 'x';
			$this->ipsclass->input['sub_length'] = 0;
		}
		
		if ( $this->ipsclass->vars['admin_group'] == $this->ipsclass->input['sub_new_group'] )
		{
			if ( $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'] )
			{
				$this->ipsclass->input['sub_new_group'] = 0;
			}
		}

		$this->ipsclass->DB->do_update( 'subscriptions', array (
																		 'sub_title'          => str_replace( "'", "", str_replace( "&#39;", "'", $this->ipsclass->input['sub_title'])),
																		 'sub_desc'           => $this->ipsclass->txt_safeslashes(trim($_POST['sub_desc'])),
																		 'sub_new_group'      => intval($this->ipsclass->input['sub_new_group']),
																		 'sub_length'         => $this->ipsclass->input['sub_length'],
																		 'sub_unit'           => $this->ipsclass->input['sub_unit'],
																		 'sub_cost'			  => $this->ipsclass->input['sub_cost'],
																		 'sub_run_module'	  => $this->ipsclass->input['sub_run_module'],
															  ), "sub_id={$row['sub_id']}" );
		
		$this->ipsclass->admin->save_log("Subscription Package '{$row['sub_title']}' edited");
		
		$this->ipsclass->main_msg = 'Subscription Package edited';
		$this->paysubs_index_packages();
	}
	
	
	/*-------------------------------------------------------------------------*/
	// Subscription Package: Alter Form (edit/new)
	/*-------------------------------------------------------------------------*/
	
	function alter_package_form($type='edit')
	{
		$this->ipsclass->input['id'] = isset($this->ipsclass->input['id']) ? intval($this->ipsclass->input['id']) : 0;
		//-------------------------------------------
		// Get packages and get methods
		//-------------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select' => 'sub_id, sub_title, sub_cost', 'from' => 'subscriptions', 'order' => 'sub_cost' ) );
		$this->ipsclass->DB->exec_query();			
		
		$packages = array();
		
		while ( $p = $this->ipsclass->DB->fetch_row() )
		{
			$packages[] = array( $p['sub_id'], $p['sub_title'] );
			
			if ( $p['sub_id'] == $subpkg_id )
			{
				$this_pkg = $p;
			}
		}
		
		$methods = array();
		
		$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'subscription_methods', 'order' => 'submethod_title' ) );
		$this->ipsclass->DB->exec_query();			
		
		while ( $m = $this->ipsclass->DB->fetch_row() )
		{
			$methods[] = array( $m['submethod_id'], $m['submethod_title'] );
			
			if ( $m['submethod_id'] == $method_id )
			{
				$this_mtd = $m;
			}
		}
		
		if ( $type == 'edit' )
		{
			if ( ! $this->ipsclass->input['id'] )
			{
				$this->ipsclass->admin->error("Could not locate a payment gateway with the id of {$this->ipsclass->input['id']}");
			}
			
			$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'subscriptions', 'where' => "sub_id={$this->ipsclass->input['id']}" ) );
			$this->ipsclass->DB->exec_query();
			
			if ( ! $row = $this->ipsclass->DB->fetch_row() )
			{
				$this->ipsclass->admin->error("Could not locate a payment gateway with the id of {$this->ipsclass->input['id']}");
			}
			
			$submit = 'Edit Package';
			$code   = 'doeditpackage';
			$table  = "Edit Package '{$row['sub_title']}'";
		}
		else
		{
			$row = array();
			$submit = "Add Package";
			$code   = "doaddpackage";
			$table  = "Add new subscription package";
		}
		
		foreach( explode( ",", $row['sub_payment_allow'] ) as $p )
		{
			$allow_payment[$p] = 1;
		}
		
		//-------------------------------------------
		// Grab member groups
		//-------------------------------------------
		
		$groups = array( 0 => array( 0, "--Don't Change Group--" ) );
		
		$this->ipsclass->DB->build_query( array( 'select' => 'g_id,g_title', 'from' => 'groups', 'order' => 'g_title' ) );
		$this->ipsclass->DB->exec_query();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			// Don't allow non-roots to create packages that they can purchase to upgrade to root
			
			if ( $this->ipsclass->vars['admin_group'] == $r['g_id'] )
			{
				if ( $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'] )
				{
					continue;
				}
			}
						
			$groups[] = array( $r['g_id'], $r['g_title'] );
		}
		
		//-------------------------------------------
		// Show form
		//-------------------------------------------
		
		$subchecked = $row['sub_unit'] == 'x' ? "checked='checked'" : '';
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , $code    ),
												  2 => array( 'act'   , 'msubs'  ),
												  3 => array( 'id'    , $this->ipsclass->input['id']),
												  4 => array( 'section', $this->ipsclass->section_code ),
									     )  );
									     
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( $table );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Package Name</b>" ,
												 							  $this->ipsclass->adskin->form_input("sub_title", $row['sub_title'] )
									   							   )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Package Description</b><br />HTML is allowed" ,
																	 		  $this->ipsclass->adskin->form_textarea("sub_desc", $row['sub_desc'] )
									    							)      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Subscription Duration</b>" ,
												  							 $this->ipsclass->adskin->form_simple_input("sub_length", $row['sub_length'] ) .
												  							 " ". $this->ipsclass->adskin->form_dropdown( 'sub_unit',
												  							 	      array( 0 => array( 'w', 'Weeks' ), 1 => array( 'm', 'Months' ), 2 => array( 'y', 'Years' ) ),
																			           $row['sub_unit'] )
																			  ." <label for='neverexpire'><b>OR</b> <input type='checkbox' id='neverexpire' value='1' name='sub_noexpire' $subchecked /> never expire.</label>",
									     							 )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Subscription Costs</b><br />Numerics and decimal points only please. Prices in your chosen default currency" ,
																			   $this->ipsclass->adskin->form_simple_input("sub_cost", $row['sub_cost'] , 7)
									    							  )      );
									     
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>New Member Group</b><br />Select the member group that the member will be moved into when payment has been cleared." ,
												 							  $this->ipsclass->adskin->form_dropdown( 'sub_new_group' , $groups , $row['sub_new_group'] ),
									     							 )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Run custom module on subscription start/end?</b><br />Optional - Advanced users only" ,
												  							 "<b>./sources/classes/paymentgateways/custom/cus_</b>".$this->ipsclass->adskin->form_simple_input("sub_run_module", $row['sub_run_module'] , 7) ."<b>.php</b><br />(File must be in this format and location)"
									     							 )      );
									     
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form($submit);
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		//-------------------------------------------
		// Quick Jump Table
		//-------------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'    , 'editpkginfo'),
												  2 => array( 'act'     , 'msubs'    ),
												  4 => array( 'section', $this->ipsclass->section_code ),
									     )  );
									     		   
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Edit Subscription / Gateway Specific Information");
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Payment Gateway</b>" ,
												 							  $this->ipsclass->adskin->form_dropdown( 'method', $methods, $row['submethod_id'] )
																	  )      );
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Subscription Package</b>" ,
																		 	  $this->ipsclass->adskin->form_dropdown( 'sub', $packages, $row['sub_id'] )
																	  )      );
												 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form( "Go!" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code.'&code=index-packages', 'Manage Packages' );
		$this->ipsclass->admin->nav[] = array( '', 'Add/Edit Packages' );
		
		$this->ipsclass->admin->output();
	}
	
	
	/*-------------------------------------------------------------------------*/
	// Payment gateway: Complete Edit
	/*-------------------------------------------------------------------------*/
	
	function edit_method_complete()
	{
		$this->ipsclass->admin->page_detail .= "<br /><b>Please make sure that you have correctly set up any third party payment gateway before allowing them here in IPB</b>";
		
		$this->ipsclass->input['id'] = isset($this->ipsclass->input['id']) ? intval($this->ipsclass->input['id']) : 0;
		
		if ( ! $this->ipsclass->input['id'] )
		{
			$this->ipsclass->admin->error("The chickens have escaped, there's feathers everywhere!");
		}
		
		$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'subscription_methods', 'where' => "submethod_id={$this->ipsclass->input['id']}" ) );
		$this->ipsclass->DB->exec_query();
		
		if ( ! $row = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->admin->error("Could locate a payment gateway with the id of {$this->ipsclass->input['id']}");
		}
		
		if ( $this->ipsclass->input['submethod_title'] == "" )
		{
			$this->ipsclass->admin->error("You must enter a valid title for this payment gateway");
		}
		
		$newbie =  array (
						   'submethod_title'    => $this->ipsclass->input['submethod_title'],
						   'submethod_desc'     => $this->ipsclass->txt_safeslashes( $_POST['submethod_desc'] ),
						   'submethod_email'    => $this->ipsclass->input['submethod_email'],
						   'submethod_active'   => intval($this->ipsclass->input['submethod_active']),
						   'submethod_sid'	    => $this->ipsclass->input['submethod_sid'],
						   'submethod_is_cc'    => intval($this->ipsclass->input['submethod_is_css']),
						   'submethod_is_auto'  => intval($this->ipsclass->input['submethod_is_auto']),
						   'submethod_use_currency' => $this->ipsclass->input['submethod_use_currency'],
				         );
											  
		foreach( array( 1,2,3,4,5 ) as $id )
		{
			if ( isset($_POST['submethod_custom_'.$id]) )
			{
				$newbie[ 'submethod_custom_'.$id ] = $this->ipsclass->txt_safeslashes( $_POST['submethod_custom_'.$id] );
			}
		}
		
		$this->ipsclass->DB->do_update( 'subscription_methods', $newbie, "submethod_id={$row['submethod_id']}" );
		
		$this->ipsclass->admin->save_log("Payment gateway '{$row['submethod_title']}' edited");
		
		$this->ipsclass->main_msg = "Payment gateway '{$row['submethod_title']}' edited";
		$this->paysubs_index_gateways();
	}
	
	/*-------------------------------------------------------------------------*/
	// Payment gateway: Edit Form
	/*-------------------------------------------------------------------------*/
	
	function edit_method()
	{
		$this->ipsclass->admin->page_detail .= "<br /><b>Please make sure that you have correctly set up any third party payment gateway before allowing them here in IPB";
		
		$this->ipsclass->input['id'] = isset($this->ipsclass->input['id']) ? intval($this->ipsclass->input['id']) : 0;
		
		if ( ! $this->ipsclass->input['id'] )
		{
			$this->ipsclass->admin->error("The chickens have escaped, there's feathers everywhere!");
		}
		
		$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'subscription_methods', 'where' => "submethod_id={$this->ipsclass->input['id']}" ) );
		$this->ipsclass->DB->exec_query();		
		
		if ( ! $row = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->admin->error("Could locate a payment gateway with the id of {$this->ipsclass->input['id']}");
		}
		
		$currency = array();
		$this_cur = "";
		
		$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'subscription_currency' ) );
		$this->ipsclass->DB->exec_query();
				
		while ( $c = $this->ipsclass->DB->fetch_row() )
		{
			$currency[] = array( $c['subcurrency_code'], $c['subcurrency_desc'] );
			
			if ( $c['subcurrency_default'] )
			{
				$this_cur = $c['subcurrency_code'];
			}
		}
		
		//---------------------------------
		// Forms like to mess up with entities
		//---------------------------------
		
		foreach( $row as $k => $v )
		{
			$row[$k] = $this->ipsclass->parse_clean_value( $v );
		}
		
		//---------------------------------
		// Load the API...
		//---------------------------------
		
		$custom = array();
		
		if ( @file_exists( ROOT_PATH . 'sources/classes/paymentgateways/class_gw_'.$row['submethod_name'].'.php' ) )
		{
			require_once( ROOT_PATH . 'sources/classes/paymentgateways/class_gw_core.php' );
			require_once( ROOT_PATH . 'sources/classes/paymentgateways/class_gw_'.$row['submethod_name'].'.php' );
			
			$this->gateway = new class_gw_module();
			
			//----------------------------------
			// Sort out the custom method fields
			//----------------------------------
			
			$form = $this->gateway->acp_return_method_variables();
			
			foreach( $form as $name => $data )
			{
				if ( $data['used'] != 0 )
				{
					$custom[] = $this->ipsclass->adskin->add_td_row( array( "<b>{$data['formname']}</b><br />{$data['formextra']}</b>" ,
														  $this->ipsclass->adskin->form_input( $name, $row[ $name ] )
												 )      );
				}
			}
			
		}
		else
		{
			$this->ipsclass->admin->error("Could not locate the API in: ".ROOT_PATH . 'sources/classes/paymentgateways/class_gw_'.$row['submethod_name'].'.php');
		}
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , 'edit_method_complete'  ),
												  2 => array( 'act'   , 'msubs'          ),
												  3 => array( 'id'    , $this->ipsclass->input['id']       ),
												  4 => array( 'section', $this->ipsclass->section_code ),
									     )  );
									     
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "{$row['submethod_title']}'s Gateway Settings" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Gateway Public Name</b>" ,
												  $this->ipsclass->adskin->form_input("submethod_title", $row['submethod_title'] )
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Gateway Public Description</b>" ,
												  $this->ipsclass->adskin->form_textarea("submethod_desc", $row['submethod_desc'] )
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Gateway Associated Email Address OR associated transaction key</b><br />This is not needed for all gateways." ,
												  $this->ipsclass->adskin->form_input("submethod_email", $row['submethod_email'] )
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Gateway Store / Seller ID</b><br />This is not needed for all gateways." ,
												  $this->ipsclass->adskin->form_input("submethod_sid", $row['submethod_sid'] )
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Gateway auto-completes order via return validation?</b><br />If the gateway does not support a return validation method, then make sure this is off." ,
												  $this->ipsclass->adskin->form_yes_no("submethod_is_auto", $row['submethod_is_auto'] )
									     )      );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Gateway default currency?</b><br />Select the currency this gateway uses." ,
												  $this->ipsclass->adskin->form_dropdown("submethod_use_currency", $currency, $row['submethod_use_currency'] )
									     )      );
									     
		if ( count( $custom ) > 0 )
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic( 'Gateway Specific Settings', 'left', 'catrow2' );
			
			$this->ipsclass->html .= implode( "\n", $custom );
		}
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Activate this Gateway?</b><br>This will allow your members to use this option." ,
												  $this->ipsclass->adskin->form_yes_no("submethod_active", $row['submethod_active'] )
									     )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Edit Settings");
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->nav[] = array( '', 'Editing '.$row['submethod_title'].' Gateway' );
		
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// Show index screen
	/*-------------------------------------------------------------------------*/
		
	function paysubs_index_gateways()
	{
		//---------------------------------------
		// INIT
		//---------------------------------------
	
		$packages_cache = array();
 		$pack_dropdown  = "";
		$trans          = array();
		$dead           = array();
		$pending        = array();
		$totals         = array();
		
		//---------------------------------------
		// Title
		//---------------------------------------
		
		$this->ipsclass->admin->page_detail .= "<br /><br />You may activate any or all of the default gateways and one of the additionally installed gateways.";
		
		//---------------------------------------
		// Make packages dropdown
		//---------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'subscriptions', 'order' => 'sub_cost ASC' ) );
		$this->ipsclass->DB->exec_query();
		
		while( $row = $this->ipsclass->DB->fetch_row() )
		{
			$packages_cache[ $row['sub_id'] ] = $row;
			
			$pack_dropdown .= $this->html->gateways_menu_item( $row );
		}
		
		//---------------------------------------
		// Show set up bit foist (Gangsta stylee)
		//---------------------------------------
		
		$this->ipsclass->DB->cache_add_query( 'intro_get_all', array(), 'sql_subsm_queries' );
		$this->ipsclass->DB->cache_exec_query();
		
		while ( $t = $this->ipsclass->DB->fetch_row() )
		{
			$trans[ strtolower($t['subtrans_method']) ] = $t;
		}
		
		$this->ipsclass->DB->cache_add_query( 'intro_get_failed_dead', array(), 'sql_subsm_queries' );
		$this->ipsclass->DB->cache_exec_query();
		            
		while ( $t = $this->ipsclass->DB->fetch_row() )
		{
			$dead[ strtolower($t['subtrans_method']) ] = $t;
		}
		
		$this->ipsclass->DB->cache_add_query( 'intro_get_failed_pending', array(), 'sql_subsm_queries' );
		$this->ipsclass->DB->cache_exec_query();
		            
		while ( $t = $this->ipsclass->DB->fetch_row() )
		{
			$pending[ strtolower($t['subtrans_method']) ] = $t;
		}
		
		//---------------------------------------
		// Show gateways
		//---------------------------------------
		
		$types         = array( 'default' => array(), 'custom' => array() );
		$total_income  = 0;
		$total_dead    = 0;
		$total_pending = 0;
		$content       = "";
		
		$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'subscription_methods' ) );
		$outer = $this->ipsclass->DB->exec_query();
		
		while ( $row = $this->ipsclass->DB->fetch_row( $outer ) )
		{
			//---------------------------------------
			// Active?
			//---------------------------------------
			
			$row['_active'] = $row['submethod_active'] == 1 ? "<span style='color:green;font-weight:bold'>Y</span>" : "<span style='color:red;font-weight:bold'>X</span>";
			
			//---------------------------------------
			// Totals
			//---------------------------------------
			
			$trans[ $row['submethod_name'] ]['revenue'] 	= isset($trans[ $row['submethod_name'] ]['revenue']) 	? $trans[ $row['submethod_name'] ]['revenue'] 	: 0;
			$dead[ $row['submethod_name'] ]['revenue']  	= isset($dead[ $row['submethod_name'] ]['revenue'])	 	? $dead[ $row['submethod_name'] ]['revenue']	: 0;
			$pending[ $row['submethod_name'] ]['revenue'] 	= isset($pending[ $row['submethod_name'] ]['revenue'])	? $pending[ $row['submethod_name'] ]['revenue']	: 0;
			$trans[ $row['submethod_name'] ]['total'] 		= isset($trans[ $row['submethod_name'] ]['total']) 		? $trans[ $row['submethod_name'] ]['total'] 	: 0;
			$dead[ $row['submethod_name'] ]['total']		= isset($dead[ $row['submethod_name'] ]['total'])		? $dead[ $row['submethod_name'] ]['total']		: 0;
			$pending[ $row['submethod_name'] ]['total']		= isset($pending[ $row['submethod_name'] ]['total']) 	? $pending[ $row['submethod_name'] ]['total'] 	: 0;			
			
			$total_income  += $trans[ $row['submethod_name'] ]['revenue'];
			$total_dead    += $dead[ $row['submethod_name'] ]['revenue'];
			$total_pending  += $pending[ $row['submethod_name'] ]['revenue'];

			$row['_total']  = $trans[ $row['submethod_name'] ]['total'] + $dead[ $row['submethod_name'] ]['total'] + $pending[ $row['submethod_name'] ]['total'];
			
			$row['_trans']   = number_format( $trans[ $row['submethod_name'] ]['revenue']  , 2, ".", "," );
			$row['_pending'] = number_format( $pending[ $row['submethod_name'] ]['revenue'], 2, ".", "," );
			$row['_dead']    = number_format( $dead[ $row['submethod_name'] ]['revenue']   , 2, ".", "," );
			
			//---------------------------------------
			// Add content
			//---------------------------------------
			
			$content .= $this->html->gateways_row( $row, str_replace( '--methodid--', $row['submethod_id'], $pack_dropdown ) );
		}
		
		//---------------------------------------
		// Work out totals
		//---------------------------------------
		
		$totals['_culm']    = number_format( $total_income + $total_dead + $total_pending, 2, ".", "," );
		$totals['_paid']    = number_format( $total_income, 2, ".", "," );
		$totals['_pending'] = number_format( $total_pending, 2, ".", "," );
		$totals['_failed']  = number_format( $total_dead, 2, ".", "," );
		
		$this->ipsclass->html .= $this->html->gateways_wrapper( $content, $totals );
		
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// Show packages screen
	/*-------------------------------------------------------------------------*/
		
	function paysubs_index_packages()
	{
		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code.'&code=index-packages', 'Manage Packages' );
		//---------------------------------------
		// INIT
		//---------------------------------------
		
		$expired        = array();
		$active         = array();
		$time           = time();
		$duration       = array( 'w' => "Week", 'm' => "Month", 'y' => "Year", 'd' => "Day" );
		$content        = "";
		
		//---------------------------------------
		// Show available plans...
		//---------------------------------------
		
		$this->ipsclass->DB->cache_add_query( 'intro_plans_a', array( 'time' => $time ), 'sql_subsm_queries' );
		$this->ipsclass->DB->cache_exec_query();
		            
		while ( $t = $this->ipsclass->DB->fetch_row() )
		{
			$expired[ $t['subtrans_sub_id'] ] = $t['total'];
		}
		
		$this->ipsclass->DB->cache_add_query( 'intro_plans_b', array( 'time' => $time ), 'sql_subsm_queries' );
		$this->ipsclass->DB->cache_exec_query();
		            
		while ( $t = $this->ipsclass->DB->fetch_row() )
		{
			$active[ $t['subtrans_sub_id'] ] = $t['total'];
		}
		
		//---------------------------------------
		// Make packages caches
		//---------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'subscriptions', 'order' => 'sub_cost ASC' ) );
		$this->ipsclass->DB->exec_query();
		
		while( $row = $this->ipsclass->DB->fetch_row() )
		{
			$row['_duration'] = $row['sub_unit'] != 'x' ? "{$row['sub_length']} {$duration[ $row['sub_unit'] ]}(s)" : "Never Expire";
			$row['_cost']     = number_format( $row['sub_cost'], 2, ".", "," );
			$row['_active']   = intval($active[ $row['sub_id'] ]);
			$row['_expired']  = intval($expired[ $row['sub_id'] ]);
			
			//---------------------------------------
			// Add content
			//---------------------------------------
			
			$content .= $this->html->packages_row( $row );
		}
		
		$this->ipsclass->html .= $this->html->packages_wrapper( $content );
		
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// Show tools screen
	/*-------------------------------------------------------------------------*/
		
	function paysubs_index_tools()
	{
		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code.'&code=index-tools', 'Manage Transactions' );
		
		//---------------------------------------
		// INIT
		//---------------------------------------
		
		$packages       = array( 0 => array( 'all', 'All packages' ) );
		$search_content = "";
		$trans_content  = "";
		$form           = array();
		
		//---------------------------------------
		// Get packages
		//---------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'subscriptions', 'order' => 'sub_cost ASC' ) );
		$this->ipsclass->DB->exec_query();
		
		while( $row = $this->ipsclass->DB->fetch_row() )
		{
			$packages[] = array( $row['sub_id'], $row['sub_title'] );
		}
		
		//---------------------------------------
		// Get states
		//---------------------------------------
		
		$state = array(
						0 => array( 'any'    , 'Any'  ),
						1 => array( 'paid'   , 'Paid' ),
						2 => array( 'failed' , 'Failed' ),
						3 => array( 'expired', 'Expired' ),
						4 => array( 'dead'   , 'Dead' ),
						5 => array( 'pending', 'Pending'),
					  );
		
		//---------------------------------------
		// Get fields
		//---------------------------------------
		
		$fields = array(
						0 => array( 'name'     , 'Member Name'     ),
						1 => array( 'trxid'    , 'Transaction ID'  ),
						2 => array( 'paid'     , 'Amount Paid'     ),
						3 => array( 'subscrid' , 'Subscription ID' ),
					   );
		
		$fields2 = array(
					    0 => array( 'none', 'Any field' ),
						1 => array( 'post', 'POST data' ),
						2 => array( 'msg' , 'Message'   ),
					   );
					   
		//---------------------------------------
		// Form elements
		//---------------------------------------
		
		$form['status']     = $this->ipsclass->adskin->form_dropdown(    "status"    , $state , $_POST['status'] );
		$form['package']    = $this->ipsclass->adskin->form_dropdown(    "package"   , $packages , $_POST['package'] );
		$form['searchtype'] = $this->ipsclass->adskin->form_dropdown(    "searchtype", $fields, $_POST['searchtype'] );
		$form['search']     = $this->ipsclass->adskin->form_simple_input("search"    , $_POST['search'], 10 );
		$form['expiredays'] = $this->ipsclass->adskin->form_simple_input("expiredays", $_POST['expiredays'], 4 );
		
		//---------------------------------------
		// Form elements 2
		//---------------------------------------
		
		
		$form['searchtype2'] = $this->ipsclass->adskin->form_dropdown(    "searchtype2", $fields2, $_POST['searchtype2'] );
		$form['search2']     = $this->ipsclass->adskin->form_simple_input("search2"    , $_POST['search2'], 10 );
	
		$this->ipsclass->html .= $this->html->tools_wrapper( $form );
		
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// Do search
	/*-------------------------------------------------------------------------*/
	
	function find_transactions()
	{
		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code.'&code=index-packages', 'Manage Transactions' );
		$this->ipsclass->admin->nav[] = array( '', 'Results' );
		
		$this->ipsclass->admin->page_detail .= "<br /><br /><b>Important Note!</b> Updating the transaction status will complete the transaction and change the member's user group.<br />
												For example, if you change the status to 'paid' - this will move the member into the new group specified in the subscription package. If you
												change the status to 'failed', 'pending' or 'expired', this will move the member back into their previous group or the default member group if
												their previous group no longer exists. If this is not desired, edit each transaction separately.
												<br />
												If the package has since been deleted, the member group will not be changed.";
		
		$st  = intval($this->ipsclass->input['st']) >=0 ? intval($this->ipsclass->input['st']) : 0;
		$end = 50;
		
		$expiredays = intval( $this->ipsclass->input['expiredays'] );
		$searchtype = trim($this->ipsclass->input['searchtype']);
		$search     = trim($this->ipsclass->input['search']);
		$package    = intval(trim($this->ipsclass->input['package']));
		$status     = trim($this->ipsclass->input['status']);
		
		$qstring    = "expiredays={$expiredays}&searchtype={$searchtype}&search={$search}&package={$package}&status={$status}";
		
		$query = array();
		
		if ( $expiredays > 0 )
		{
			$date    = time() + $expiredays * 86400;
			$query[] = "s.subtrans_end_date < $date";
		}
		
		if ( $search != "" )
		{
			switch ( $searchtype )
			{
				case 'name':
					$this->ipsclass->DB->cache_add_query( 'get_lower_like', array( 'name' => $search ), 'sql_subsm_queries' );
					$this->ipsclass->DB->cache_exec_query();
					
					$ids = array();
					
					while( $mem = $this->ipsclass->DB->fetch_row() )
					{
						$ids[] = $mem['id'];
					}
					
					if ( count($ids) > 0 )
					{
						$query[] = "s.subtrans_member_id IN (".implode(",", $ids ).")";
					}
					break;
				case 'trxid':
					$query[] = 's.subtrans_trxid="'.$search.'"';
					break;
				case 'paid':
					$query[] = "s.subtrans_paid='".$search."'";
				
				default:
					break;
			}
		}
		
		if ( $package > 0 )
		{
			$query[] = "s.subtrans_sub_id=$package";
		}
		
		if ( $status != "" AND $status != "any" )
		{
			$query[] = "s.subtrans_state='$status'";
		}
		
		if ( count($query) > 0 )
		{
			$middle_query = implode( " AND ", $query );
		}
		else
		{
			$middle_query = "1=1";
		}
		
		//-------------------------------------------
		// Get a count...
		//-------------------------------------------
		
		$t = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'COUNT(*) as count', 'from' => 'subscription_trans s', 'where' => $middle_query ) );
		
		$cnt = intval( $t['count'] );
		
		//-------------------------------------------
		// Page links...
		//-------------------------------------------
		
		$links = $this->ipsclass->adskin->build_pagelinks( array( 'TOTAL_POSS'  => $cnt,
											   'PER_PAGE'    => 50,
											   'CUR_ST_VAL'  => $st,
											   'L_SINGLE'    => "Single Page",
											   'L_MULTI'     => "Multiple Pages",
											   'BASE_URL'    => $this->ipsclass->base_url."&{$this->ipsclass->form_code}&code=find_transactions&".$qstring,
									  )      );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'    , 'domodifytrans'),
												  2 => array( 'act'     , 'msubs'        ),
												  3 => array( 'qstring' , $qstring       ),
												  4 => array( 'section', $this->ipsclass->section_code ),
									     )  );
									     		   
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"       , "3%" );
		$this->ipsclass->adskin->td_header[] = array( "Member Name"  , "20%" );
		$this->ipsclass->adskin->td_header[] = array( "Email"        , "20%" );
		$this->ipsclass->adskin->td_header[] = array( "Package"      , "15%" );
		$this->ipsclass->adskin->td_header[] = array( "Paid"         , "10%" );
		$this->ipsclass->adskin->td_header[] = array( "Started"      , "10%" );
		$this->ipsclass->adskin->td_header[] = array( "Expires"      , "10%" );
		$this->ipsclass->adskin->td_header[] = array( "Status"       , "12%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Transactions Found" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic( "$links", "right");
		
		$this->ipsclass->DB->cache_add_query( 'do_search', array( 'query' => "WHERE ".$middle_query, 'st' => $st, 'end' => $end ), 'sql_subsm_queries' );
		$this->ipsclass->DB->cache_exec_query();		
		
		if ( ! $this->ipsclass->DB->get_num_rows() )
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic( "No matches found", "center");
		}
		else
		{
			while ( $row = $this->ipsclass->DB->fetch_row() )
			{
				if ( $row['sub_title'] == "" )
				{
					$row['sub_title'] = "<i>Since Deleted</i>";
				}
				
				if ( $row['id'] == "" )
				{
					$row['name']  = "<i>Member Deleted (ID: {$row['subtrans_member_id']})</i>";
					$row['email'] = "<i>Member Since Deleted</i>";
				}
				
				$color = "";
				
				switch( $row['subtrans_state'] )
				{
					case 'paid':
						$color = 'green';
						break;
					case 'dead':
						$color = 'gray';
						break;
					case 'pending':
						$color = 'orange';
						break;
					case 'failed':
						$color = 'red';
						break;
					case 'expired':
						$color = 'gray';
						break;
					default:
						$color = 'black';
						break;
				}
				
				$end_date = $row['sub_unit'] == 'x' ? 'Lifetime' : $this->ipsclass->get_date( $row['subtrans_end_date'], 'JOINED', 1 );
				
				if( !$row['sub_unit'] )
				{
					$end_date = '<i>N/A</i>';
				}
				
				$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<center><input type='checkbox' name='subtrans_id_{$row['subtrans_id']}' value='1' /></center>" ,
														  "<b><a href='{$this->ipsclass->vars['board_url']}/index.php?showuser={$row['subtrans_member_id']}' target='_blank'>{$row['name']}</a></b><br /><span style='color:green'>[ <a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=edittransaction&id={$row['subtrans_id']}' style='color:green'>Edit Transaction</a> ]</span>",
														  "{$row['email']}",
														  "{$row['sub_title']}",
														  "{$row['subtrans_paid']}",
														  "<center>" . $this->ipsclass->get_date( $row['subtrans_start_date'], 'JOINED', 1 ) . "</center>",
														  "<center>" . $end_date . "</center>",
														  "<center><span style='color:$color'>" . strtoupper( $row['subtrans_state'] ) . "</span></center>",
												 )      );
			}
			
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( array( "<div align='right'><a href='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=addtransaction' class='fauxbutton'>Manually Add New Transaction</a> &nbsp; &nbsp; &nbsp; &nbsp; <input type='submit' id='button' name='delete' value='DELETE' /> or <b>update selected entries to</b></div>", 7 ) ,
																					    $this->ipsclass->adskin->form_dropdown( 'updateto', array( 0 => array( 'paid'   , 'Paid'    ),
																																				   1 => array( 'pending', 'Pending' ),
																																				   2 => array( 'failed' , 'Failed'  ),
																																				   3 => array( 'expired', 'Expired' ) )
																															  )
																			  )      );
											 
		}
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form_standalone("Update");
		
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// Do search log
	/*-------------------------------------------------------------------------*/
	
	function find_logs()
	{
		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code.'&code=index-packages', 'Manage Packages' );
		$this->ipsclass->admin->nav[] = array( '', 'Results' );
		//-------------------------------------------
		// INIT
		//-------------------------------------------
		
		$st         = intval($this->ipsclass->input['st']) >=0 ? intval($this->ipsclass->input['st']) : 0;
		$end        = 50;
		$searchtype = trim($this->ipsclass->input['searchtype']);
		$search     = trim($this->ipsclass->input['search']);
		$qstring    = "searchtype={$searchtype}&search={$search}";
 		$query      = array();
		
		//-------------------------------------------
		// Searching?
		//-------------------------------------------
		
		if ( $search != "" )
		{
			switch ( $searchtype )
			{
				case 'post':
					$query[] = 'sublog_postdata LIKE "%'.$search.'%"';
					break;
				case 'msg':
					$query[] = "sublog_data LIKE '%".$search."%'";
				
				default:
					break;
			}
		}
		
		if ( count($query) > 0 )
		{
			$middle_query = implode( " AND ", $query );
		}
		else
		{
			$middle_query = "1=1";
		}
		
		//-------------------------------------------
		// Get a count...
		//-------------------------------------------
		
		$t = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'COUNT(*) as count', 'from' => 'subscription_logs', 'where' => $middle_query ) );
		
		$cnt = intval( $t['count'] );
		
		//-------------------------------------------
		// Page links...
		//-------------------------------------------
		
		$links = $this->ipsclass->adskin->build_pagelinks( array( 'TOTAL_POSS'  => $cnt,
														  'PER_PAGE'    => 50,
														  'CUR_ST_VAL'  => $st,
														  'L_SINGLE'    => "Single Page",
														  'L_MULTI'     => "Multiple Pages",
														  'BASE_URL'    => $this->ipsclass->base_url."&{$this->ipsclass->form_code}&code=find_logs&".$qstring,
												 )      );
		
		$this->ipsclass->adskin->td_header[] = array( "ID"           , "5%" );
		$this->ipsclass->adskin->td_header[] = array( "Message"      , "55%" );
		$this->ipsclass->adskin->td_header[] = array( "IP"           , "10%" );
		$this->ipsclass->adskin->td_header[] = array( "POST"         , "10%" );
		$this->ipsclass->adskin->td_header[] = array( "Date"         , "20%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Transaction Entries Found" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic( "$links", "right");
		
		$this->ipsclass->DB->cache_add_query( 'do_search_two', array( 'query' => "WHERE ".$middle_query, 'st' => $st, 'end' => $end ), 'sql_subsm_queries' );
		$this->ipsclass->DB->cache_exec_query();
				
		if ( ! $this->ipsclass->DB->get_num_rows() )
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic( "No matches found", "center");
		}
		else
		{
			while ( $row = $this->ipsclass->DB->fetch_row() )
			{
				
				$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<center>{$row['sublog_id']}</center>" ,
																					 "{$row['sublog_data']}",
																					 "{$row['sublog_ipaddress']}",
																					 "<center><a href='javascript:pop_win(\"&{$this->ipsclass->form_code_js}&code=find_logs_view_entry&id={$row['sublog_id']}\", \"PostData\", 300,500)'>View</a></center>",
																					 "<center>" . $this->ipsclass->get_date( $row['sublog_date'], 'SHORT' ) . "</center>",
																			)      );
			}
											 
		}
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// Show POST DATA
	/*-------------------------------------------------------------------------*/
	
	function find_logs_view_entry()
	{
		//-------------------------------------------
		// Get log
		//-------------------------------------------
		
		$id = intval($this->ipsclass->input['id']);
		
		$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'subscription_logs', 'where' => "sublog_id=$id" ) );
		$this->ipsclass->DB->exec_query();
		
		if ( ! $row = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->admin->error("Cannot get sub log entry, no record for id $id");
		}
		
		$post_data = explode( "\n", $row['sublog_postdata'] );
		
		//-------------------------------------------
		// Set up the table header
		//-------------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "Key"    , "20%" );
		$this->ipsclass->adskin->td_header[] = array( "Value"  , "80%" );
		
		//-------------------------------------------
		// Start the table
		//-------------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "POST DATA FOR TR_ID: $id" );
		
		foreach( $post_data as $data )
		{
			list( $key, $value ) = explode( "=", $data, 2 );
			
			if ( $key == "" )
			{
				continue;
			}
			
			$this->ipsclass->html .=  $this->ipsclass->adskin->add_td_row( array( trim($key), preg_replace( "/;$/", "", trim($value) ) ) );
		}
							     
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->print_popup();
	}
									     
	/*-------------------------------------------------------------------------*/
	// Save a transaction!
	/*-------------------------------------------------------------------------*/
	
	function save_transaction($type='edit')
	{
		$save = array();
		
		$this->ipsclass->input['id'] = isset($this->ipsclass->input['id']) ? intval($this->ipsclass->input['id']) : 0;
		$this->ipsclass->input['subtrans_sub_id'] = isset($this->ipsclass->input['subtrans_sub_id']) ? intval($this->ipsclass->input['subtrans_sub_id']) : 0;
		
		if ( $type == 'edit' )
		{
			if ( $this->ipsclass->input['id'] == "" )
			{
				$this->ipsclass->admin->error("No ID was passed - please go back and try again");
			}
			
			$subtrans = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'subscription_trans', 'where' => "subtrans_id=".$this->ipsclass->input['id'] ) );
			
			$save['subtrans_member_id'] = $subtrans['subtrans_member_id'];
			
			$mem = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'members', 'where' => "id={$save['subtrans_member_id']}" ) );
		}
		else
		{
			if ( $this->ipsclass->input['membername'] == "" )
			{
				$this->edit_transaction( $type, "You must enter a valid name" );
			}
			
			$name = strtolower( str_replace( '|', "&#124;", $this->ipsclass->input['membername'] ) );
			
			$this->ipsclass->DB->cache_add_query( 'get_lower_name', array( 'name' => $name ), 'sql_subsm_queries' );
			$this->ipsclass->DB->cache_exec_query();
			
			if ( ! $mem = $this->ipsclass->DB->fetch_row() )
			{
				$this->edit_transaction( $type, "Could not locate a member called '{$this->ipsclass->input['membername']}'" );
			}
			
			$save['subtrans_member_id']  = $mem['id'];
			$save['subtrans_start_date'] = time();
		}
		
		//-------------------------------------------
		// Check...
		//-------------------------------------------
		
		$date_count = 0;
		
		foreach( array( 'month', 'day', 'year' ) as $i )
		{
			if ( $this->ipsclass->input[ $i ] )
			{
				$date_count++;
			}
		}
		
		if ( $date_count > 0 and $date_count < 3 )
		{
			$this->edit_transaction( $type, "You must complete the expiry date fully" );
		}
		
		if ( $this->ipsclass->input['subtrans_paid'] == "" )
		{
			$this->edit_transaction( $type, "Please enter a valid total for the amount paid" );
		}
		
		if ( $date_count )
		{
			if ( ! checkdate( $this->ipsclass->input['month'], $this->ipsclass->input['day'] , $this->ipsclass->input['year'] ) )
			{
				$this->edit_transaction( $type, "You have entered an impossible expiry date - please check your input" );
			}
		
			$new_expiry = mktime( 11, 59, 59, $this->ipsclass->input['month'], $this->ipsclass->input['day'], $this->ipsclass->input['year'] );
			
			if ( $new_expiry < time() )
			{
				$this->edit_transaction( $type, "You cannot set an expiry date before the start of the subscription." );
			}
		}
		else
		{
			$new_expiry = 9999999999;
		}
		
		$groups = array();
		
		foreach( $this->ipsclass->cache['group_cache'] as $id => $data )
		{
			if( $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group']
				AND $id == $this->ipsclass->vars['admin_group'] )
			{
				continue;
			}
						
			$groups[] = $id;
		}

		$save['subtrans_method']     = $this->ipsclass->input['subtrans_method'];
		$save['subtrans_end_date']   = $new_expiry;
		$save['subtrans_sub_id']     = $this->ipsclass->input['subtrans_sub_id'];
		$save['subtrans_state']      = $this->ipsclass->input['subtrans_state'];
		$save['subtrans_old_group']  = in_array( $this->ipsclass->input['subtrans_old_group'], $groups ) ? $this->ipsclass->input['subtrans_old_group']: 0;
		$save['subtrans_paid']       = $this->ipsclass->input['subtrans_paid'];
		$save['subtrans_cumulative'] = $this->ipsclass->input['subtrans_paid'];
		
		$default = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'subscription_currency', 'where' => "subcurrency_default=1" ) );
		
		$save['subtrans_currency']  = $default['subcurrency_code'];
		
		if ( $type == 'edit' )
		{
			$this->ipsclass->DB->do_update( 'subscription_trans', $save, "subtrans_id={$this->ipsclass->input['id']}" );
		}
		else
		{
			$this->ipsclass->DB->do_insert( 'subscription_trans', $save );
			
			$this->ipsclass->input['id'] = $this->ipsclass->DB->get_insert_id();
			
			//-------------------------------------------
			// Was it paid?
			//-------------------------------------------
			
			if ( $save['subtrans_state'] == 'paid' )
			{
				$this->send_success_email( $mem );
			}
		}
		
		//----------------------------------------
		// Sort out member
		//----------------------------------------
		
		$sub = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'subscriptions', 'where' => "sub_id={$this->ipsclass->input['subtrans_sub_id']}" ) );
		
		if ( $sub['sub_new_group'] )
		{
			if ( $this->ipsclass->input['subtrans_state'] == 'paid' )
			{
				$this->ipsclass->DB->do_update( "members", array( 'mgroup'  => intval($sub['sub_new_group']),
												  'sub_end' => $new_expiry,
												), "id={$save['subtrans_member_id']}" );
			}
			
			if ( USE_MODULES == 1 )
			{
				$this->modules->register_class($this);
				$this->modules->on_group_change($save['subtrans_member_id'], $sub['sub_new_group']);
			}
			
			$name = preg_replace( "/[^a-zA-Z0-9\-\_]/", "" , $sub['sub_run_module'] );
    	
			if ( $name != "" )
			{
				if ( @file_exists( ROOT_PATH . 'sources/classes/paymentgateways/custom/cus_'.$name.'.php' ) )
				{
					require_once( ROOT_PATH . 'sources/classes/paymentgateways/custom/cus_'.$name.'.php' );
					
					$this->customsubs = new customsubs();
					
					$this->customsubs->subs_paid($sub, $mem, $this->ipsclass->input['id']);
				}
			}
		}
		else
		{
			$this->ipsclass->DB->do_update( "members", array( 'sub_end' => $new_expiry ), "id={$save['subtrans_member_id']}" );
		}
		
		$this->ipsclass->boink_it( $this->ipsclass->base_url."&{$this->ipsclass->form_code}&code=find_transactions" );
	}
	
	/*-------------------------------------------------------------------------*/
	// Edit a transaction!
	/*-------------------------------------------------------------------------*/
	
	function edit_transaction($type='edit', $error="")
	{
		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code.'&code=index-tools', 'Manage Transactions' );
		$this->ipsclass->admin->nav[] = array( '', 'Add/Edit Transactions' );
		//-------------------------------------------
		// Set up
		//-------------------------------------------
		
		$state = array(
						1 => array( 'paid'   , 'Paid' ),
						2 => array( 'failed' , 'Failed' ),
						3 => array( 'expired', 'Expired' ),
						4 => array( 'dead'   , 'Dead' ),
						5 => array( 'pending', 'Pending'),
					  );
		
		$this->ipsclass->DB->build_query( array( 'select' => 'sub_id, sub_title, sub_cost', 'from' => 'subscriptions', 'order' => 'sub_cost' ) );
		$this->ipsclass->DB->exec_query();
		
		$packages = array();
		
		while ( $p = $this->ipsclass->DB->fetch_row() )
		{
			$packages[] = array( $p['sub_id'], $p['sub_title']." ({$p['sub_cost']})" );
		}
		
		$methods = array();
		
		$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'subscription_methods', 'order' => 'submethod_title' ) );
		$this->ipsclass->DB->exec_query();		
		
		while ( $m = $this->ipsclass->DB->fetch_row() )
		{
			$methods[] = array( $m['submethod_name'], $m['submethod_title'] );
		}
		
		$groups = array( 0 => array( 0, "--Don't Change Group--" ) );
		
		foreach( $this->ipsclass->cache['group_cache'] as $id => $r )
		{
			if( $this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group']
				AND $r['g_id'] == $this->ipsclass->vars['admin_group'] )
			{
				continue;
			}
			
			$groups[] = array( $r['g_id'], $r['g_title'] );
		}
		
		//-------------------------------------------
		// Do the twist and shout.
		//-------------------------------------------
		
		if ( $type == 'edit' )
		{
			$this->ipsclass->input['id'] = isset($this->ipsclass->input['id']) ? intval($this->ipsclass->input['id']) : 0;
			
			if ( $this->ipsclass->input['id'] == "" )
			{
				$this->ipsclass->admin->error("No ID was passed - please go back and try again");
			}
			
			$this->ipsclass->DB->cache_add_query( 'edit_trans', array( 'id' => $this->ipsclass->input['id'] ), 'sql_subsm_queries' );
			$this->ipsclass->DB->cache_exec_query();
			
			if ( ! $row = $this->ipsclass->DB->fetch_row() )
			{
				$this->ipsclass->admin->error("Could not find a subscription transaction with the id {$this->ipsclass->input['id']}");
			}
			
			if ( $row['name'] == "" )
			{
				$row['name'] = "<i>Member Since Deleted (ID: {$row['subtrans_member_id']})</i>";
			}
			
			$code   = "doedittransaction";
			$button = "Complete Edit";
			$table  = "Edit Transaction";
			$name   = $row['name'];
			
			if ( $row['sub_unit'] == 'x' )
			{
				$month = '';
				$day   = '';
				$year  = '';
			}
			else
			{
				list( $month, $day, $year ) = explode( ",", gmdate( 'n,j,Y', $row['subtrans_end_date'] ) );
			}
			
		}
		else
		{
			$code   = "doaddtransaction";
			$button = "Complete Entry";
			$table  = "Add Transaction";
			$name   = $this->ipsclass->adskin->form_input( 'membername' , $this->ipsclass->input['membername']). " <a href='{$this->ipsclass->vars['board_url']}/index.php?act=Members' target='_blank'>Find Members</a>";
			$row    = array();
			
			list( $month, $day, $year ) = explode( ",", gmdate( 'n,j,Y' ) );
		}
		
		//-------------------------------------------
		// Error?
		//-------------------------------------------
		
		if ( $error != "" )
		{
			$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "100%" );
		
			$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Error" );
			
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( $error )  );
			
			$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		}
		
		//-------------------------------------------
		// Carry on!
		//-------------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'    , $code     ),
																 2 => array( 'act'     , 'msubs'   ),
																 3 => array( 'id'      , $this->ipsclass->input['id'] ),
																 4 => array( 'section', $this->ipsclass->section_code ),
														)      );
									     		   
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "60%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( $table );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Member Name</b>" ,
											               	     $name,
									                    )      );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Package Subscribed</b>" ,
												                             $this->ipsclass->adskin->form_dropdown("subtrans_sub_id", $packages, $this->ipsclass->input['subtrans_sub_id'] == "" ? $row['subtrans_sub_id'] : $this->ipsclass->input['subtrans_sub_id'])
									                                )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Previous Group</b>" ,
												                             $this->ipsclass->adskin->form_dropdown("subtrans_old_group", $groups, $this->ipsclass->input['subtrans_old_group'] == "" ? $row['subtrans_old_group'] : $this->ipsclass->input['subtrans_old_group'] )
									                                )      );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Amount Paid</b><br />Numerics and decimal points only please. Prices in your currency default" ,
												                             $this->ipsclass->adskin->form_simple_input("subtrans_paid", $this->ipsclass->input['subtrans_paid'] == "" ? $row['subtrans_paid'] : $this->ipsclass->input['subtrans_paid'] , 7)
									                                )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Payment Method</b>" ,
												                             $this->ipsclass->adskin->form_dropdown("subtrans_method", $methods, $this->ipsclass->input['subtrans_method'] == "" ? strtolower($row['subtrans_method']) : $this->ipsclass->input['subtrans_method'] )
									                                )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Payment Status</b>" ,
																             $this->ipsclass->adskin->form_dropdown("subtrans_state", $state, $this->ipsclass->input['subtrans_state'] == "" ? $row['subtrans_state'] : $this->ipsclass->input['subtrans_state'] )
														            )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Expires</b><br />MM DD YYYY<div class='graytext'>Leave all fields blank to never expire this transaction</div>" ,
															            	 $this->ipsclass->adskin->form_simple_input("month", $this->ipsclass->input['month'] == "" ? $month : $this->ipsclass->input['month'], 2 )." ".
															            	 $this->ipsclass->adskin->form_simple_input("day"  , $this->ipsclass->input['day']   == "" ? $day   : $this->ipsclass->input['day']  , 2 )." ".
															            	 $this->ipsclass->adskin->form_simple_input("year" , $this->ipsclass->input['year']  == "" ? $year  : $this->ipsclass->input['year'] , 4 )." (Max: 2037)"
														            )      );
									     
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form( $button );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// DELETE teh trannies!
	/*-------------------------------------------------------------------------*/
	
	function do_delete_trans()
	{
		//-------------------------------------------
		// Grab member groups
		//-------------------------------------------
		
		$groups = array();
		
		$this->ipsclass->DB->build_query( array( 'select' => 'g_id,g_title', 'from' => 'groups', 'order' => 'g_title' ) );
		$this->ipsclass->DB->exec_query();
				
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$groups[ $r['g_id'] ] = 1;
		}
		
		$ids = explode( ",", $this->ipsclass->input['ids'] );
		
		$id_count = count($ids);
		
		if ( $id_count < 1 )
		{
			$this->ipsclass->admin->error("You did not select any transactions to modify");
		}
		
		$ids = $this->ipsclass->clean_int_array($ids);
		
		$this->ipsclass->DB->cache_add_query( 'delete_trans', array( 'ids' => $ids ), 'sql_subsm_queries' );
		$outer = $this->ipsclass->DB->cache_exec_query();
		
		while ( $row = $this->ipsclass->DB->fetch_row( $outer ) )
		{
			if ( $row['subtrans_state'] == 'paid' )
			{
				$change_to_group = intval($row['subtrans_old_group']);
			}
			
			if ( $change_to_group > 0 )
			{
				if ( $groups[ $change_to_group ] != 1 )
				{
					$change_to_group = $INFO['member_group'];
				}
				
				if ( $row['subtrans_member_id'] != "" )
				{
					$this->ipsclass->DB->do_update( "members", array( 'mgroup'  => $change_to_group,
													  'sub_end' => 0,
													), "id={$row['subtrans_member_id']}" );
				}
				
				if ( USE_MODULES == 1 )
				{
					$this->modules->register_class($this);
					$this->modules->on_group_change($row['subtrans_member_id'], $change_to_group);
				}
			}
			else
			{
				$this->ipsclass->DB->do_update( "members", array( 'sub_end' => 0 ), "id={$row['subtrans_member_id']}" );
			}
			
			$name = preg_replace( "/[^a-zA-Z0-9\-\_]/", "" , $row['sub_run_module'] );
    	
			if ( $name != "" )
			{
				if ( @file_exists( ROOT_PATH . 'sources/classes/paymentgateways/custom/cus_'.$name.'.php' ) )
				{
					require_once( ROOT_PATH . 'sources/classes/paymentgateways/custom/cus_'.$name.'.php' );
					
					$this->customsubs = new customsubs();
					
					if ( $row['subtrans_state'] == 'paid' )
					{
						$this->customsubs->subs_failed($row, $row, $row['subtrans_id']);
					}
				}
			}
		}
		
		$this->ipsclass->DB->do_delete( 'subscription_trans', "subtrans_id IN (".implode(",", $ids).")" );
		
		$this->ipsclass->admin->save_log("$id_count subscription transactions deleted");
	
		$this->ipsclass->boink_it( $this->ipsclass->base_url."&{$this->ipsclass->form_code}&code=find_transactions" );
	}
	
	/*-------------------------------------------------------------------------*/
	// Modify teh trannies!
	/*-------------------------------------------------------------------------*/
	
	function do_modify_trans()
	{
		$day_to_seconds = array( 'd' => 86400,
								 'w' => 604800,
								 'm' => 2592000,
								 'y' => 31536000,
							   );
		
		//-------------------------------------------
		// Grab member groups
		//-------------------------------------------
		
		$groups = array();
		
		$this->ipsclass->DB->build_query( array( 'select' => 'g_id,g_title', 'from' => 'groups', 'order' => 'g_title' ) );
		$this->ipsclass->DB->exec_query();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$groups[ $r['g_id'] ] = 1;
		}
		
		//-------------------------------------------
		// Get incoming IDS
		//-------------------------------------------
		
		$ids = array();
		
		foreach ($this->ipsclass->input as $key => $value)
 		{
 			if ( preg_match( "/^subtrans_id_(\d+)$/", $key, $match ) )
 			{
 				if ($this->ipsclass->input[$match[0]])
 				{
 					$ids[] = $match[1];
 				}
 			}
 		}
 		
 		$ids = $this->ipsclass->clean_int_array( $ids );
		
		if ( count($ids) < 1 )
		{
			$this->ipsclass->admin->error("You did not select any transactions to modify");
		}
		
		$id_string = implode( ",", $ids );
		
		$id_count  = count($ids);
		
		//---------------------------------------
		// Was delete pressed?
		// How the hell should I know?
		// What is this, the magic oracle??
		//---------------------------------------
		
		if ( $this->ipsclass->input['delete'] != "" )
		{
			$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'    , 'dotransdelete' ),
																	             2 => array( 'act'     , 'msubs'         ),
																            	 3 => array( 'ids'     , $id_string      ),
																            	 4 => array( 'section', $this->ipsclass->section_code ),
															            )  );
									     		   
			$this->ipsclass->adskin->td_header[] = array( "&nbsp;"  , "100%" );
			
			$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Removal Confirmation" );
			
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Transactions to remove: $id_count</b><br /><br />Deleting these transactions will remove all subscribed members and return them back to their previous group.
												                                 Please note that if the group that they were in no longer exists, they will be moved into the default member group. The cumulative revenue will be recalculated."
									                                    )      );
			
			$this->ipsclass->html .= $this->ipsclass->adskin->end_form( "Delete" );
			
			$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
			
			$this->ipsclass->admin->output();
		}
		else
		{
			
			$this->ipsclass->DB->cache_add_query( 'delete_trans', array( 'ids' => $ids ), 'sql_subsm_queries' );
			$outer = $this->ipsclass->DB->cache_exec_query();
			
			while ( $row = $this->ipsclass->DB->fetch_row( $outer ) )
			{
				$change_to_group = 0;
				
				if ( $this->ipsclass->input['updateto'] == 'paid' )
				{
					if ( $row['subtrans_state'] != 'paid' )
					{
						$change_to_group = intval($row['sub_new_group']);
					}
					
					if ( $row['sub_unit'] == 'x' )
					{
						$change_date = 9999999999;
					}
					else
					{
						$change_date = time() + ( $row['sub_length'] * $day_to_seconds[ $row['sub_unit'] ] );
					}
				}
				else
				{
					//-------------------------------------------
					// Was it paid?
					//-------------------------------------------
					
					if ( $row['subtrans_state'] == 'paid' )
					{
						$change_to_group = intval($row['subtrans_old_group']);
					}
					
					$change_date     = 0;
				}
				
				if ( $change_to_group > 0 )
				{
					if ( $groups[ $change_to_group ] != 1 )
					{
						$change_to_group = $this->ipsclass->vars['member_group'];
					}
					
					if ( $row['sub_id'] != "" and $row['subtrans_member_id'] != "" )
					{
						$this->ipsclass->DB->do_update( "members", array( 'mgroup'  => $change_to_group, 'sub_end' => $change_date ), "id={$row['subtrans_member_id']}" );
					}
					
					if ( USE_MODULES == 1 )
					{
						$this->modules->register_class($this);
						$this->modules->on_group_change($row['subtrans_member_id'], $change_to_group);
					}
				}
				else
				{
					if ( $row['sub_id'] != "" and $row['subtrans_member_id'] != "" )
					{
						$this->ipsclass->DB->do_update( "members", array( 'sub_end' => $change_date ), "id={$row['subtrans_member_id']}" );
					}
				}
				
				$this->ipsclass->DB->do_update( 'subscription_trans', array( 'subtrans_state' => $this->ipsclass->input['updateto'] ), "subtrans_id={$row['subtrans_id']}" );
				
				$name = preg_replace( "/[^a-zA-Z0-9\-\_]/", "" , $row['sub_run_module'] );
    	
				if ( $name != "" )
				{
					if ( @file_exists( ROOT_PATH . 'sources/classes/paymentgateways/custom/cus_'.$name.'.php' ) )
					{
						require_once( ROOT_PATH . 'sources/classes/paymentgateways/custom/cus_'.$name.'.php' );
						
						$this->customsubs = new customsubs();
						
						if ( $this->ipsclass->input['updateto'] == 'paid' )
						{
							//-------------------------------------------
							// New is paid and current is paid?
							//-------------------------------------------
							
							if ( $row['subtrans_state'] != 'paid' )
							{
								$this->customsubs->subs_paid($row, $row, $row['subtrans_id']);
							}
						}
						else
						{
							//-------------------------------------------
							// Changing from paid to not paid?
							//-------------------------------------------
							
							if ( $row['subtrans_state'] == 'paid' )
							{
								$this->customsubs->subs_failed($row, $row, $row['subtrans_id']);
							}
						}
					}
				}
				
				//-------------------------------------------
				// Was it paid?
				//-------------------------------------------
				
				if ( $row['subtrans_state'] == 'paid' )
				{
					$this->send_success_email( $row );
				}
			}
			
			$this->ipsclass->admin->save_log("{$id_count} subscription transactions updated to {$this->ipsclass->input['updateto']}");
		
			$this->ipsclass->boink_it( $this->ipsclass->base_url."&{$this->ipsclass->form_code}&code=find_transactions&".trim($_POST['qstring']) );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Unsubscribe members
	/*-------------------------------------------------------------------------*/
	
	function _unsub_members($sub_id, $type='all', $mark='expired')
	{
		//-------------------------------------------
		// Grab member groups
		//-------------------------------------------
		
		$groups = array();
		
		$this->ipsclass->DB->build_query( array( 'select' => 'g_id,g_title', 'from' => 'groups', 'order' => 'g_title' ) );
		$this->ipsclass->DB->exec_query();

		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$groups[ $r['g_id'] ] = 1;
		}
		
		//-------------------------------------------
		// I'm a little query!
		//-------------------------------------------
		
		$qe = "";
		
		if ( $type != 'all' )
		{
			$qe = " AND s.subtrans_end_date < ".time();
		}
		
		$this->ipsclass->DB->cache_add_query( 'unsub_members', array( 'id' => intval($this->ipsclass->input['id']), 'qe' => $qe), 'sql_subsm_queries' );
		$outer = $this->ipsclass->DB->cache_exec_query();
		
		while ( $row = $this->ipsclass->DB->fetch_row( $outer ) )
		{
			if ( $mark == 'paid' )
			{
				$change_date = $row['sub_length'] ? time() + ( $row['sub_length'] * $day_to_seconds[ $row['sub_unit'] ] ) : 0;
			}
			else
			{
				$change_date = 0;
			}
			
			//---------------------
			// If we're not paid, 
			// leave alone..
			//---------------------
			
			if ( $row['subtrans_state'] != 'paid' )
			{
				$row['subtrans_old_group'] = 0;
			}
			
			if ( intval($row['subtrans_old_group']) > 0 )
			{
				if ( $groups[ $row['subtrans_old_group'] ] != 1 )
				{
					$row['subtrans_old_group'] = $INFO['member_group'];
				}
				
				if ( ! $row['subtrans_member_id'] )
				{
					continue;
				}
				
				$this->ipsclass->DB->do_update( "members", array( 'mgroup'  => $row['subtrans_old_group'], 'sub_end' => $change_date ), "id={$row['subtrans_member_id']}" );
				
				if ( USE_MODULES == 1 )
				{
					$this->modules->register_class($this);
					$this->modules->on_group_change($row['subtrans_member_id'], $change_to_group);
				}
			}
			else
			{
				$this->ipsclass->DB->do_update( "members", array(  'sub_end' => $change_date ), "id={$row['subtrans_member_id']}" );
			}
			
			$this->ipsclass->DB->do_update( "subscription_trans", array( 'subtrans_state' => $mark ), "subtrans_id={$row['subtrans_id']}" );
												
			$name = preg_replace( "/[^a-zA-Z0-9\-\_]/", "" , $row['sub_run_module'] );
    	
			if ( $name != "" )
			{
				if ( @file_exists( ROOT_PATH . 'sources/classes/paymentgateways/custom/cus_'.$name.'.php' ) )
				{
					require_once( ROOT_PATH . 'sources/classes/paymentgateways/custom/cus_'.$name.'.php' );
					
					$this->customsubs = new customsubs();
					
					$this->customsubs->subs_failed($row, $row, $row['subtrans_id']); // Your boat?
				}
			}
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Send success email
	/*-------------------------------------------------------------------------*/
	
	function send_success_email( $member )
	{
		//--------------------------------------
    	// Make sure we have enough member info
    	//--------------------------------------
    	
    	if ( ! $member['email'] )
    	{
    		$member = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'members', 'where' => 'id='.intval($member['id']) ) );
    	}
    	
    	//--------------------------------------
    	// Get enuff details for the thingy
    	//--------------------------------------
    	
    	$this->ipsclass->DB->build_query( array( 'select'   => 'st.subtrans_sub_id, st.subtrans_member_id, st.subtrans_end_date',
												 'from'     => array( 'subscription_trans' => 'st' ),
												 'where'    => 'st.subtrans_member_id='.intval($member['id'])." AND st.subtrans_state='paid'",
												 'add_join' => array( 0 => array( 'select' => 's.sub_title',
																				  'from'   => array( 'subscriptions' => 's' ),
																				  'where'  => 's.sub_id=st.subtrans_sub_id',
																				  'type'   => 'left' ) ) ) );
																				  
		$this->ipsclass->DB->exec_query();
		
		$row = $this->ipsclass->DB->fetch_row();
		
		$this->email->get_template("new_subscription");
		$this->email->build_message( array(
											'PACKAGE'  => $row['sub_title'],
											'EXPIRES'  => $this->ipsclass->get_date( $row['subtrans_end_date'], 'DATE', 1 ),
											'LINK'     => $this->ipsclass->vars['board_url'].'/index.'.$this->ipsclass->vars['php_ext'].'?act=paysubs&CODE=index',
								   )     );
		
		$this->email->to = trim( $member['email'] );
		$this->email->send_mail();
	}
}


?>