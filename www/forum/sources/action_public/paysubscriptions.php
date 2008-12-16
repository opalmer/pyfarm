<?php
/*
+--------------------------------------------------------------------------
|   Invision Power Board
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2005 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Email: matt@invisionpower.com
+---------------------------------------------------------------------------
|   INVISION POWER BOARD IS NOT FREE SOFTWARE!
|   http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
|
|   > Subsmanager Public Action Script
|   > Script written by Matt Mecham
|   > Date started: 31st March 2005 (14:45)
|
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class paysubscriptions
{
	# Global
	var $ipsclass;
	var $lib;
	var $class      = "";
	var $module     = "";
	var $html       = "";
	var $ucp_html   = "";
	var $member     = "";
	var $nav        = "";
	var $page_title = "";
	var $gateway    = "";
	var $method     = "";
	var $method_name = "";
	var $day_to_seconds = array( 'd' => 86400,
								 'w' => 604800,
								 'm' => 2592000,
								 'y' => 31536000,
							   );
							   
	var $all_currency  = array();
	var $def_currency  = array();
	var $cho_currency  = array();
	var $is_from_ucp   = 1;
	
	/*-------------------------------------------------------------------------*/
	// Constructer, called and run by IPB
	/*-------------------------------------------------------------------------*/
	
	function auto_run()
	{
		//--------------------------------------------
		// Check..
		//--------------------------------------------
		
		if ( ! defined( 'IPB_CALLED' ) )
		{
			define( 'IPB_CALLED', 1 );
		}
		
		//--------------------------------------------
		// From the CP?
		//--------------------------------------------
		
		if ( $this->ipsclass->input['nocp'] )
		{
			$this->is_from_ucp = 0;
		}
		
		//--------------------------------------------
    	// Require the HTML and language modules
    	//--------------------------------------------
    	
		$this->ipsclass->load_language('lang_ucp' );
		$this->ipsclass->load_language('lang_subscriptions' );
		
		$this->ipsclass->load_template('skin_subscriptions');
    	
    	//--------------------------------------------
		// Load extra db cache file
		//--------------------------------------------
		
		$this->ipsclass->DB->load_cache_file( ROOT_PATH.'sources/sql/'.SQL_DRIVER.'_subsm_queries.php', 'sql_subsm_queries' );
		
		//---------------------------------------------
		// Load Payment handler
		//---------------------------------------------
		
		require_once( ROOT_PATH . 'sources/handlers/han_paysubscriptions.php' );
		$this->gateway           = new han_paysubscriptions();
		$this->gateway->ipsclass =& $this->ipsclass;
		$this->gateway->main_init();
		
		//=====================================
		// Set up structure
		//=====================================
		
		switch( $this->ipsclass->input['CODE'] )
		{
			case 'paymentmethod':
				$this->_load_menu();
				$this->do_payment_method();
				break;
				
			case 'paymentscreen':
				$this->_load_menu();
				$this->do_payment_screen();
				break;
				
			case 'incoming':
				$this->do_validate_payment();
				break;
				
			case 'custom':
				$this->run_custom();
				break;
				
			case 'cancelfromreg':
				$this->cancel_from_reg();
				break;
				
			default:
				$this->_load_menu();
				$this->do_index();
				break;
		}
		
		if ( $this->is_from_ucp )
		{
			$fj = $this->ipsclass->build_forum_jump();
			
			$this->output .= $this->ipsclass->compiled_templates['skin_subscriptions']->CP_end();
			
			$this->output .= $this->ipsclass->compiled_templates['skin_subscriptions']->forum_jump( $fj );
		}
		else
		{
			$this->output .= $this->ipsclass->compiled_templates['skin_subscriptions']->sub_no_cp_end();
		}
		
		//--------------------------------------
		// Any special message?
		//--------------------------------------
		
		$this->ipsclass->input['msgtype'] = isset($this->ipsclass->input['msgtype']) ? $this->ipsclass->input['msgtype'] : '';
		
		if ( $this->ipsclass->input['msgtype'] == 'fromreg' )
		{
			$msg = $this->ipsclass->compiled_templates['skin_subscriptions']->sub_msg_fromreg();
		}
		else if ( $this->ipsclass->input['msgtype'] == 'force' )
		{
			$msg = $this->ipsclass->compiled_templates['skin_subscriptions']->sub_msg_force();
		}
		else if ( $this->ipsclass->input['msgtype'] == 'general' )
		{
			$msg = $this->ipsclass->compiled_templates['skin_subscriptions']->sub_msg_general();
		}
		else
		{
			$msg = "";
		}
		
		if ( $msg )
		{
			$this->ipsclass->print->to_print = str_replace( "<!--{MSG}-->", $msg, $this->ipsclass->print->to_print );
		}
		
		$this->nav[] = "<a href='".$this->ipsclass->base_url."act=UserCP&amp;CODE=00'>".$this->ipsclass->lang['t_title']."</a>";
		$this->nav[] = "<a href='".$this->ipsclass->base_url."act=paysubs&amp;CODE=index'>".$this->ipsclass->lang['s_page_title']."</a>";
    	
    	$this->ipsclass->print->add_output( $this->output );
        $this->ipsclass->print->do_output( array( 'TITLE' => $this->ipsclass->lang['s_page_title'], 'JS' => 1, 'NAV' => $this->nav ) );
		
	}
	
	/*-------------------------------------------------------------------------*/
	// Cancel purchase, remove pkg ID from members
	/*-------------------------------------------------------------------------*/
	
	function cancel_from_reg()
	{
		$this->ipsclass->DB->do_update( 'members', array( 'subs_pkg_chosen' => 0 ), 'id='.intval($this->ipsclass->member['id']) );
		
		$this->ipsclass->boink_it( $this->ipsclass->base_url );
	}
	
	
	/*-------------------------------------------------------------------------*/
	// Do return payment screen
	/*-------------------------------------------------------------------------*/
	
	function do_validate_payment()
	{
		$type = preg_replace( "/[^a-zA-Z0-9\-\_]/", "" , $this->ipsclass->input['type'] );
		
		if ( $type == "" )
		{
			$this->gateway->do_log("Tried to return validate but failed: No type set");
			$this->_end_process();
		}
		
		//--------------------------------------
		// Try to get row in DB
		//--------------------------------------
		
		$this->ipsclass->DB->cache_add_query( 'mod_custom', array( 'type' => $type ), 'sql_subsm_queries' );
		$this->ipsclass->DB->cache_exec_query();
		
		$method = $this->ipsclass->DB->fetch_row();
		
		if ( ! $method['submethod_id'] )
		{
			$this->gateway->do_log("Tried to return validate but failed: No such method as '$type'");
			$this->_end_process();
		}
		
		//---------------------------------------------
		// INIT Load Payment handler
		//---------------------------------------------

		$this->gateway->gateway  = $method['submethod_name'];
		$this->gateway->gateway_init();
		
		if ( $this->gateway->error )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'subs_no_api' ) );
		}
		
		//---------------------------------------------
		// Pass off to API handler
		//---------------------------------------------
		
		$this->gateway->validate_payment( $method );
	}
	
	/*-------------------------------------------------------------------------*/
	// Show API Payment screen
	/*-------------------------------------------------------------------------*/
	
	function do_payment_screen()
	{
		$cur_id        = intval($this->ipsclass->input['curid']);
		$upgrade       = intval($this->ipsclass->input['upgrade']);
		$sub_chosen    = intval($this->ipsclass->input['sub']);
		$method_chosen = intval($this->ipsclass->input['methodid']);
		
		if ( $sub_chosen < 1 )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'subs_no_selected' ) );
		}
		
		if ( $method_chosen < 1 )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'subs_nomethod_selected' ) );
		}
		
		$method = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'subscription_methods', 'where' => "submethod_id={$method_chosen}" ) );
		
		$subs = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'subscriptions', 'where' => "sub_id={$sub_chosen}" ) );
		
		$extra = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'subscription_extra', 'where' => "subextra_sub_id={$sub_chosen} AND subextra_method_id={$method_chosen}" ) );
		
		//---------------------------------------------
		// INIT Load Payment handler
		//---------------------------------------------

		$this->gateway->gateway  = $method['submethod_name'];
		$this->gateway->gateway_init();
		
		if ( $this->gateway->error )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'subs_no_api' ) );
		}
		
		//---------------------------------------------
		// Make sure we don't recurr on lifetime pkgs
		//---------------------------------------------
		
		if ( $subs['sub_unit'] == 'x' )
		{
			$extra['subextra_recurring'] = 0;
		}
		
		if ( $upgrade ) 
		{
			$current = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'subscriptions', 'where' => "sub_id={$cur_id}" ) );
			
			if ( $method['submethod_name'] == 'manual' )
			{
				$this->output .= $this->_show_manual_upgrade($current, $subs, $method, $extra);
			}
			else
			{
				$this->output .= $this->gateway->show_upgrade_payment_screen($current, $subs, $method, $extra);
			}
		}
		else
		{
			if ( $method['submethod_name'] == 'manual' )
			{
				$this->output .= $this->_show_manual_normal($subs, $method, $extra);
			}
			else
			{
				$this->output .= $this->gateway->show_normal_payment_screen($subs, $method, $extra);
			}
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Show Available Payment Methods
	/*-------------------------------------------------------------------------*/
	
	function do_payment_method()
	{
		$cur_id      = isset($this->ipsclass->input['curid']) ? intval($this->ipsclass->input['curid']) : 0;
		$upgrade     = isset($this->ipsclass->input['upgrade']) ? intval($this->ipsclass->input['upgrade']) : 0;
		$sub_chosen  = isset($this->ipsclass->input['sub']) ? intval($this->ipsclass->input['sub']) : 0;
		$subs        = array();
		$upg_methods = array();
		$all_methods = array();
		
		if ( $sub_chosen < 1 )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'subs_no_selected' ) );
		}
		
		//--------------------------------------------
    	// Get all packages
		//--------------------------------------------
			
		$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'subscriptions', 'order' => 'sub_cost' ) );
		$this->ipsclass->DB->exec_query();
		
		while ( $s = $this->ipsclass->DB->fetch_row() )
		{
			$subs[ $s['sub_id'] ] = $s;
		}
		
		//--------------------------------------------
    	// Get all gateways [we can upgrade with]
		//--------------------------------------------
		
		$this->ipsclass->DB->cache_add_query( 'mod_payment_method', array(), 'sql_subsm_queries' );
		$this->ipsclass->DB->cache_exec_query();
		
		while ( $m = $this->ipsclass->DB->fetch_row() )
		{
			if ( $m['submethod_active'] == 1 AND $m['subextra_can_upgrade'] == 1 )
			{
				$upg_methods[ $m['submethod_id'] ] = $m;
			}
			
			$all_methods[ $m['submethod_id'] ] = $m;
		}
		
		if ( $upgrade != 0 )
		{
			//--------------------------------------------
    		// We're upgrading!! Yay! - Get cur subs
			//--------------------------------------------
			
			$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'subscription_trans', 'where' => "subtrans_member_id={$this->ipsclass->member['id']} AND subtrans_sub_id={$cur_id} AND subtrans_state='paid'" ) );
			$this->ipsclass->DB->exec_query();			
			
			if ( ! $cur_trans = $this->ipsclass->DB->fetch_row() )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'subs_no_curid' ) );
			}
			
			//--------------------------------------------
    		// Check stuff
			//--------------------------------------------
			
			if ( ! is_array( $subs[ $cur_id ] ) )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'subs_no_curid' ) );
			}
			
			if ( count($upg_methods) < 1 )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'subs_no_upgrade' ) );
			}
			
			//--------------------------------------------
    		// Still here? Good - summary and show methods
			//--------------------------------------------
			
			$balance  = $subs[ $sub_chosen ]['sub_cost'] - $cur_trans['subtrans_paid'];
			
			$end_date = ( $subs[ $sub_chosen ]['sub_unit'] == 'x' or $subs[ $cur_id ]['sub_unit'] == 'x' )
					  ? $this->ipsclass->lang['no_expire']
					  : $this->ipsclass->get_date( $cur_trans['subtrans_end_date'], 'JOINED' );
					  
			$new_date = ( $sub_upgrade['sub_unit'] == 'x' or $sub_current['sub_unit'] == 'x' )
						? $this->ipsclass->lang['no_expire']
						: $this->ipsclass->get_date( ( $cur_trans['subtrans_end_date'] - ($subs[ $cur_trans['subtrans_sub_id'] ]['sub_length'] * $this->day_to_seconds[$subs[ $cur_trans['subtrans_sub_id'] ]['sub_unit']]) + ($subs[ $sub_chosen ]['sub_length'] * $this->day_to_seconds[$subs[ $sub_chosen ]['sub_unit']])) , 'JOINED' );					  
			
			$this->ipsclass->lang['sc_upgrade_string'] = sprintf( $this->ipsclass->lang['sc_upgrade_string'],
															$subs[ $cur_trans['subtrans_sub_id'] ]['sub_title'],
															$subs[ $sub_chosen ]['sub_title'],
															$end_date,
															sprintf( "%.2f", $balance * $this->gateway->cho_currency['subcurrency_exchange'] ) . ' '.$this->gateway->cho_currency['subcurrency_code'],
															$new_date
														  );
			
			$this->output .= $this->ipsclass->compiled_templates['skin_subscriptions']->sub_two_upgrade_summary();
			
			$this->output .= $this->ipsclass->compiled_templates['skin_subscriptions']->sub_two_methods_top($sub_chosen, $upgrade, $cur_id, $this->gateway->cho_currency['subcurrency_code']);
			
			foreach( $upg_methods as $id => $method )
			{
				$this->output .= $this->ipsclass->compiled_templates['skin_subscriptions']->sub_two_methods_row($id, $method['submethod_title'],$method['submethod_desc']);
			}
			
			$this->output .= $this->ipsclass->compiled_templates['skin_subscriptions']->sub_two_methods_bottom();
			
			$this->output .= $this->ipsclass->compiled_templates['skin_subscriptions']->sub_two_methods_continue_button();
		
		}
		else
		{
			//--------------------------------------------
    		// We're not upgrading!! Boo(bies)! :0
			//--------------------------------------------
			
			$this->ipsclass->lang['sc_normal_string'] = sprintf( $this->ipsclass->lang['sc_normal_string'],
														   $subs[ $sub_chosen ]['sub_title'],
														   sprintf( "%.2f", $subs[ $sub_chosen ]['sub_cost'] * $this->gateway->cho_currency['subcurrency_exchange'] ) . ' '.$this->gateway->cho_currency['subcurrency_code']
														  );
			
			$this->output .= $this->ipsclass->compiled_templates['skin_subscriptions']->sub_two_normal_summary();
			
			$this->output .= $this->ipsclass->compiled_templates['skin_subscriptions']->sub_two_methods_top($sub_chosen, $upgrade, $cur_id,  $this->gateway->cho_currency['subcurrency_code']);
			
			foreach( $all_methods as $id => $method )
			{
				$this->output .= $this->ipsclass->compiled_templates['skin_subscriptions']->sub_two_methods_row($id, $method['submethod_title'],$method['submethod_desc']);
			}
			
			$this->output .= $this->ipsclass->compiled_templates['skin_subscriptions']->sub_two_methods_bottom();
			
			$this->output .= $this->ipsclass->compiled_templates['skin_subscriptions']->sub_two_methods_continue_button();
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Show Index (Default subs page)
	/*-------------------------------------------------------------------------*/
	
	function do_index()
	{
		$current = array();
		$dead    = array();
		$subs    = array();
		
		//--------------------------------------------
    	// Get all packages
		//--------------------------------------------
			
		$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'subscriptions', 'order' => 'sub_cost' ) );
		$this->ipsclass->DB->exec_query();		
		
		while ( $s = $this->ipsclass->DB->fetch_row() )
		{
			$subs[ $s['sub_id'] ] = $s;
		}
		
		//--------------------------------------------
    	// Get all transactions with our memberid
		//--------------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'subscription_trans', 'where' => "subtrans_member_id={$this->ipsclass->member['id']}" ) );
		$this->ipsclass->DB->exec_query();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			if ( $r['subtrans_state'] == 'expired' OR $r['subtrans_state'] == 'dead' OR $r['subtrans_state'] == 'failed' )
			{
				$dead[ $r['subtrans_id'] ] = $r;
			}
			else
			{
				$current[ $r['subtrans_id'] ] = $r;
			}
		}
		
		//--------------------------------------------
    	// Show dead / expired subs
		//--------------------------------------------
			
		if ( count($dead) > 0 )
		{
			$this->output .= $this->ipsclass->compiled_templates['skin_subscriptions']->sub_choose_dead_top();
			
			foreach( $dead as $did => $didnt )
			{
				$end_date = ($subs[ $didnt['subtrans_sub_id'] ]['sub_unit'] == 'x') ? $this->ipsclass->lang['no_expire'] : $this->ipsclass->get_date($cdata['subtrans_end_date'], 'JOINED', 1);
				
				$this->output .= $this->ipsclass->compiled_templates['skin_subscriptions']->sub_choose_dead_row( $did,
																												 $subs[ $didnt['subtrans_sub_id'] ]['sub_title'],
																												 $this->ipsclass->get_date($didnt['subtrans_start_date'], 'JOINED', 1),
																												 $end_date,
																												 sprintf( "%.2f", $didnt['subtrans_paid'] * $this->gateway->cho_currency['subcurrency_exchange'] ),
																												 $this->ipsclass->lang['pay_'.strtolower($didnt['subtrans_state'])]
																											   );
			}
			
			$this->output .= $this->ipsclass->compiled_templates['skin_subscriptions']->sub_choose_dead_bottom();
		}
		
		//--------------------------------------------
    	// We have current subscriptions?
		//--------------------------------------------
		
		$max_cost = 0;
		$max_id   = 0;
		$max_data = 0;
			
		if ( count($current) > 0 )
		{
			$this->output .= $this->ipsclass->compiled_templates['skin_subscriptions']->sub_choose_current_top();
			
			foreach( $current as $cid => $cdata )
			{
				$end_date = ($subs[ $cdata['subtrans_sub_id'] ]['sub_unit'] == 'x') ? $this->ipsclass->lang['no_expire'] : $this->ipsclass->get_date($cdata['subtrans_end_date'], 'JOINED', 1);
				
				$this->output .= $this->ipsclass->compiled_templates['skin_subscriptions']->sub_choose_current_row( $cid,
																	  $subs[ $cdata['subtrans_sub_id'] ]['sub_title'],
																	  $this->ipsclass->get_date($cdata['subtrans_start_date'], 'JOINED', 1),
																	  $end_date,
																	  sprintf( "%.2f", $cdata['subtrans_paid'] * $this->gateway->cho_currency['subcurrency_exchange'] ),
																	  $this->ipsclass->lang['pay_'.strtolower($cdata['subtrans_state'])]
																	 );
																	 
				if ( $subs[ $cdata['subtrans_sub_id'] ]['sub_cost'] > $max_cost )
				{
					$max_cost = $subs[ $cdata['subtrans_sub_id'] ]['sub_cost'];
					$max_id   = $cdata['subtrans_sub_id'];
					$max_data = $cdata;
					$max_state = $cdata['subtrans_state'];
					$max_end   = $subs[ $cdata['subtrans_sub_id'] ]['sub_unit'];
				}
			}
			
			$this->output .= $this->ipsclass->compiled_templates['skin_subscriptions']->sub_choose_current_bottom();
			
			//--------------------------------------------
    		// Do we have any upgradeable packages?
    		// First, check the gateways
    		// CHECK: Are we pending? If so - don't allow
    		// any upgrades until it's paid
			//--------------------------------------------
			
			$can_upgrade = 0;
			
			if ( $max_state != 'pending' )
			{
				$this->ipsclass->DB->cache_add_query( 'mod_do_index', array(), 'sql_subsm_queries' );
				$this->ipsclass->DB->cache_exec_query();
							
				while ( $m= $this->ipsclass->DB->fetch_row() )
				{
					if ( $m['submethod_active'] == 1 AND $m['subextra_can_upgrade'] == 1 )
					{
						$can_upgrade = 1;
						break;
					}
				}
			}
			
			if ( $can_upgrade == 1 )
			{
				//--------------------------------------------
				// So far so good, now lets check if we can
				// have anywhere to go (ie. we're not on the top tier)
				//--------------------------------------------
				
				$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'subscriptions', 'where' => "sub_cost > {$max_cost}", 'order' => 'sub_cost' ) );
				$this->ipsclass->DB->exec_query();
				
				if ( $this->ipsclass->DB->get_num_rows() )
				{
					// We have some!
					
					$this->output .= $this->ipsclass->compiled_templates['skin_subscriptions']->sub_choose_upgrade_top($max_id, $this->gateway->cho_currency['subcurrency_code']);
					
					while ( $row = $this->ipsclass->DB->fetch_row() )
					{
						$date = ($max_data['subtrans_end_date'] - ($subs[ $cdata['subtrans_sub_id'] ]['sub_length'] * $this->day_to_seconds[$max_end]) + ($row['sub_length'] * $this->day_to_seconds[$row['sub_unit']]) );
						
						$end_date = ($row['sub_unit'] == 'x' or $max_end == 'x') ? $this->ipsclass->lang['no_expire'] : $this->ipsclass->get_date( $date, 'JOINED', 1 );
						
						$this->output .= $this->ipsclass->compiled_templates['skin_subscriptions']->sub_choose_upgrade_row( $row['sub_id'],
																			  $row['sub_title'],
																			  $row['sub_desc'],
																			  sprintf( "%.2f", ($row['sub_cost'] - $max_cost)  * $this->gateway->cho_currency['subcurrency_exchange'] ),
																			  $end_date
																			);
					}
					
					$this->output .= $this->ipsclass->compiled_templates['skin_subscriptions']->sub_choose_upgrade_bottom();
				
				}
				else
				{
					// We don't!
				
				}
			}
		}
		else
		{
			//--------------------------------------------
    		// Show new subs
			//--------------------------------------------
			
			$this->output .= $this->ipsclass->compiled_templates['skin_subscriptions']->sub_choose_new_top($this->gateway->cho_currency['subcurrency_code']);
			
			foreach( $subs as $row )
			{
				$duration = $row['sub_length'];
			
				if ( $duration > 1 )
				{
					$duration .= ' '.$this->ipsclass->lang[ 'timep_'.$row['sub_unit'] ];
				}
				else
				{
					$duration .= ' '.$this->ipsclass->lang[ 'time_'.$row['sub_unit'] ];
				}
				
				$end_date = ($row['sub_unit'] == 'x') ? $this->ipsclass->lang['no_expire'] : $duration;
				
				$this->output .= $this->ipsclass->compiled_templates['skin_subscriptions']->sub_choose_new_row( $row['sub_id'],
																  $row['sub_title'],
																  $row['sub_desc'],
																  sprintf( "%.2f", $row['sub_cost']  * $this->gateway->cho_currency['subcurrency_exchange'] ),
																  $end_date
																);
			}
			
			$this->output .= $this->ipsclass->compiled_templates['skin_subscriptions']->sub_choose_new_bottom();
		}
		
		$this->output .= $this->ipsclass->compiled_templates['skin_subscriptions']->sub_currency_change_form( $this->_make_currency_dropdown(), $this->ipsclass->base_url.'act=paysubs&amp;CODE=index' );
		
		$this->output .= $this->ipsclass->compiled_templates['skin_subscriptions']->sub_page_bottom();
	}
	
	/*-------------------------------------------------------------------------*/
	// Load Menu
	/*-------------------------------------------------------------------------*/
	
	function _load_menu()
	{
		if ( ! $this->is_from_ucp )
		{
			$menu_html = $this->ipsclass->compiled_templates['skin_subscriptions']->sub_no_cp_start();
			$this->ipsclass->print->add_output( $menu_html );
			return;
		}
		
    	//--------------------------------------------
    	// Check viewing permissions, etc
		//--------------------------------------------
		
		if ( empty($this->ipsclass->member['id']) or $this->ipsclass->member['id'] == "" or $this->ipsclass->member['id'] == 0 )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_guests' ) );
		}
		
		//--------------------------------------------
		// Load class
		//--------------------------------------------
		
		require_once( ROOT_PATH."sources/lib/func_usercp.php" );
		
		$this->ipsclass->load_template( 'skin_ucp' );
		$this->ipsclass->load_language( 'lang_ucp' );
		
		//-----------------------------------------
		// INIT da func
		//-----------------------------------------
		
    	$this->lib   		 =  new func_usercp();
    	$this->lib->ipsclass =& $this->ipsclass;
		$this->lib->class    =& $this;
    	
    	//-----------------------------------------
    	// Print menu
    	//-----------------------------------------
    	
    	$this->ipsclass->print->add_output( $this->lib->ucp_generate_menu() );
    }
    
    
    /*-------------------------------------------------------------------------*/
	// Make currency drop down box baby
	/*-------------------------------------------------------------------------*/
	
	function _make_currency_dropdown()
	{
		$curr_box = $this->ipsclass->compiled_templates['skin_subscriptions']->sub_currency_change_top();
		
		$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'subscription_currency' ) );
		$this->ipsclass->DB->exec_query();
		
		while ( $c = $this->ipsclass->DB->fetch_row() )
		{
			$default = "";
			
			if ( isset($this->ipsclass->input['currency']) AND $this->ipsclass->input['currency'] )
			{
				if ( $this->ipsclass->input['currency'] == $c['subcurrency_code'] )
				{
					$default = " selected='selected'";
				}
			}
			else
			{
				if ( $c['subcurrency_default'] )
				{
					$default = " selected='selected'";
				}
			}
			
			$curr_box .= $this->ipsclass->compiled_templates['skin_subscriptions']->sub_currency_change_row( $c['subcurrency_code'], $c['subcurrency_desc'], $default );
		}
		
		$curr_box .= $this->ipsclass->compiled_templates['skin_subscriptions']->sub_currency_change_bottom();
		
		return $curr_box;
	}
	
    /*-------------------------------------------------------------------------*/
	// End process
	/*-------------------------------------------------------------------------*/
	
	function _end_process($dont_die_for_me_argentina=0)
	{
		if ( $this->return_not_die )
		{
			$this->_load_menu();
			$this->do_index();
			
			$fj = $this->ipsclass->build_forum_jump();
		
			$this->output .= $this->ipsclass->compiled_templates['skin_subscriptions']->CP_end();
			
			$this->output .= $this->ipsclass->compiled_templates['skin_subscriptions']->forum_jump($fj, $links);
			
			$this->nav[] = "<a href='".$this->ipsclass->base_url."act=UserCP&amp;CODE=00'>".$this->ipsclass->lang['t_title']."</a>";
			$this->nav[] = "<a href='".$this->ipsclass->base_url."act=paysubs&amp;CODE=index'>".$this->ipsclass->lang['s_page_title']."</a>";
			
			$this->ipsclass->print->add_output("$this->output");
			$this->ipsclass->print->do_output( array( 'TITLE' => $this->ipsclass->lang['s_page_title'], 'JS' => 1, NAV => $this->nav ) );
		}
		else
		{
			if ( $dont_die_for_me_argentina != 1 )
			{
				exit();
			}
		}
	}
    
	
	/*-------------------------------------------------------------------------*/
	// HANDLE CUSTOM STUFF
	/*-------------------------------------------------------------------------*/
	
	function run_custom()
	{
		switch( $this->ipsclass->input['mode'] )
		{
			case 'ticket':
				$this->do_ticket();
				break;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Pop up meh ticket
	/*-------------------------------------------------------------------------*/
	
	function do_ticket()
	{
		$sub_id  = intval( $this->ipsclass->input['sid'] );
		$tick_id = intval( $this->ipsclass->input['tickid'] );
		$upgrade = intval( $this->ipsclass->input['upgrade'] );
		$sub_title_extra = "";
		
		//---------------------------------------
		// Check for pending subscription
		//---------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'subscription_trans', 'where' => "subtrans_member_id={$this->ipsclass->member['id']} AND subtrans_state='pending' AND subtrans_end_date > ".time() ) );
		$this->ipsclass->DB->exec_query();		
		
		if ( $trx = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'sub_already' ) );
		}
		
		
		if ( $sub_id < 1 )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'subs_fail', 'EXTRA' => 'no_curid' ) );
		}
		
		$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'subscriptions', 'where' => "sub_id={$sub_id}" ) );
		$this->ipsclass->DB->exec_query();	
		
		if ( ! $sub = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'subs_fail', 'EXTRA' => 'no_curid' ) );
		}
		
		$old_group = ($sub[ 'sub_new_group' ] > 0) ? $this->ipsclass->member['mgroup'] : 0;
		
		//-------------------------
		// start array
		//-------------------------
		
		$use_meh = array(
							'subtrans_sub_id'     => $sub_id,
							'subtrans_member_id'  => $this->ipsclass->member['id'],
							'subtrans_old_group'  => $old_group,
							'subtrans_paid'       => $sub['sub_cost'],
							'subtrans_cumulative' => $sub['sub_cost'],
							'subtrans_method'	  => 'manual',
							'subtrans_start_date' => time(),
							'subtrans_end_date'   => time() + ( $sub['sub_length'] * $this->day_to_seconds[ $sub['sub_unit'] ] ),
							'subtrans_state'      => 'pending'
					    );
					    
		if ( $sub['sub_unit'] == 'x' )
		{
			$use_meh['subtrans_end_date'] = 9999999999;
		}
		
		if ( $upgrade == 1 )
		{
			//-------------------------
			// Check out me bad self
			//-------------------------
			
			if ( $tick_id < 1 )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'subs_fail', 'EXTRA' => 'no_curid' ) );
			}
			
			$this->ipsclass->DB->build_query( array( 'select' 	=> 't.*', 
													 'from' 	=> array( 'subscription_trans' => 't' ), 
													 'where' 	=> "t.subtrans_id={$tick_id}",
													 'add_join'	=> array( array( 'type'		=> 'left',
													 							 'select'	=> 's.sub_length, s.sub_unit',
													 							 'from'		=> array( 'subscriptions' => 's' ),
													 							 'where'	=> 's.sub_id=t.subtrans_sub_id'
													 					)		)
											) 		);
			$this->ipsclass->DB->exec_query();
			
			if ( ! $trans = $this->ipsclass->DB->fetch_row() )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'subs_fail', 'EXTRA' => 'no_curid' ) );
			}
			
			$use_meh['subtrans_paid'] = $sub['sub_cost'] - $trans['subtrans_paid'];
			
			unset($use_meh['sub_cumulative']);
			unset($use_meh['sub_start_date']);
			unset($use_meh['sub_end_date']);
			
			$use_meh['sub_end_date'] = $trans['subtrans_end_date'] + ( $sub['sub_length'] * $this->day_to_seconds[ $sub['sub_unit'] ] );
			$use_meh['sub_end_date'] -= ($trans['sub_length'] * $this->day_to_seconds[ $trans['sub_unit'] ] );
			
			$this->ipsclass->DB->do_update( 'subscription_trans', array_merge( $use_meh, array( 'subtrans_cumulative' => "subtrans_cumulative+{$sub['sub_cost']}" ) ), "subtrans_id={$tick_id}" );
			
			$sub_title_extra = '('. $this->ipsclass->lang['gw_upgrade'] .')';
			
		}
		else
		{
			//-------------------------
			// Chow-mow!
			//-------------------------
			
			$dbs = $this->ipsclass->DB->compile_db_insert_string($use_meh);
			
			$this->ipsclass->DB->do_insert( 'subscription_trans', $use_meh );
			
			$tick_id = $this->ipsclass->DB->get_insert_id();
			
		}
		
		$cost = sprintf( "%.2f", $use_meh['subtrans_paid'] * $this->gateway->cho_currency['subcurrency_exchange'] ) . ' '.$this->gateway->cho_currency['subcurrency_code'];
		
		$html = $this->ipsclass->compiled_templates['skin_subscriptions']->show_ticket($sub, $tick_id, $cost, $sub_title_extra);
		
		$this->ipsclass->print->pop_up_window("TICKET", $html );
		
	}
	
	/*-------------------------------------------------------------------------*/
	// MANUAL NORMAL
	/*-------------------------------------------------------------------------*/
	
	function _show_manual_normal($sub_upgrade, $pay_method, $extra)
	{
		//---------------------------------------
		// Check we have chosen package details
		//---------------------------------------
		
		if ( ! $sub_upgrade['sub_id'] )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'subs_fail', 'EXTRA' => 'no_curid' ) );
		}
		
		//---------------------------------------
		// Format the info string
		//---------------------------------------
		
		$cost = sprintf( "%.2f", $sub_upgrade['sub_cost'] * $this->gateway->cho_currency['subcurrency_exchange'] );
		
		$this->ipsclass->lang['sc_normal_string'] = sprintf( $this->ipsclass->lang['sc_normal_string'],
													   $sub_upgrade['sub_title'],
													   $cost . ' ' . $this->gateway->cho_currency['subcurrency_code']
													 );
		
		
		$this->ipsclass->lang['post_manual_more'] = sprintf( $this->ipsclass->lang['post_manual_more'],
													   $pay_method['submethod_custom_1'],
													   $pay_method['submethod_custom_2'],
													   $pay_method['submethod_custom_3'],
													   $pay_method['submethod_custom_4'],
													   $pay_method['submethod_custom_5'] );
		
		return $this->ipsclass->compiled_templates['skin_subscriptions']->do_manual_normal_screen(
															$sub_upgrade['sub_id'], 
															$this->ipsclass->lang['sc_normal_string'],
															$this->gateway->cho_currency['subcurrency_code']
														  );
		
		
	}
	
	/*-------------------------------------------------------------------------*/
	// MANUAL UPGRADE
	/*-------------------------------------------------------------------------*/
	
	function _show_manual_upgrade($sub_current, $sub_upgrade, $pay_method, $extra)
	{
		//---------------------------------------
		// Check we can do upgrades
		//---------------------------------------
	
		if ( $this->can_do_upgrades != 1 )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'subs_no_upgrade_poss' ) );
		}
		
		//---------------------------------------
		// Check we have current package details
		//---------------------------------------
		
		if ( ! $sub_current['sub_id'] )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'subs_fail', 'EXTRA' => 'no_curid' ) );
		}
		
		//---------------------------------------
		// Check we have upgrade to package details
		//---------------------------------------
		
		if ( ! $sub_upgrade['sub_id'] )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'subs_fail', 'EXTRA' => 'no_curid' ) );
		}
		
		//--------------------------------------------
		// We're upgrading!! Yay! - Get cur subs
		//--------------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'subscription_trans', 'where' => "subtrans_member_id={$this->ipsclass->member['id']} AND subtrans_sub_id={$sub_current['sub_id']} AND subtrans_state='paid'" ) );
		$this->ipsclass->DB->exec_query();	

		if ( ! $cur_trans = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'subs_no_curid' ) );
		}
		
		//---------------------------------------
		// Format the info string
		//---------------------------------------
		
		$balance = $sub_upgrade['sub_cost'] - $cur_trans['subtrans_paid'];
		
		$end_date = ( $sub_upgrade['sub_unit'] == 'x' or $sub_current['sub_unit'] == 'x' )
					  ? $this->ipsclass->lang['no_expire']
					  : $this->ipsclass->get_date( $cur_trans['subtrans_end_date'], 'JOINED' );
					  
		$new_date = ( $sub_upgrade['sub_unit'] == 'x' or $sub_current['sub_unit'] == 'x' )
					? $this->ipsclass->lang['no_expire']
					: $this->ipsclass->get_date( ( $cur_trans['subtrans_end_date'] - ($sub_current['sub_length'] * $this->day_to_seconds[$sub_current['sub_unit']]) + ($sub_upgrade['sub_length'] * $this->day_to_seconds[$sub_upgrade['sub_unit']])) , 'JOINED' );					  
					  
		$this->ipsclass->lang['sc_upgrade_string'] = sprintf( $this->ipsclass->lang['sc_upgrade_string'],
														$sub_current['sub_title'],
														$sub_upgrade['sub_title'],
														$end_date,
														'&#036;'.$balance,
														$new_date
													  );
													  
		$this->ipsclass->lang['post_manual_more'] = sprintf( $this->ipsclass->lang['post_manual_more'],
													   $pay_method['submethod_custom_1'],
													   $pay_method['submethod_custom_2'],
													   $pay_method['submethod_custom_3'],
													   $pay_method['submethod_custom_4'],
													   $pay_method['submethod_custom_5'] );
		
		
		
		return $this->ipsclass->compiled_templates['skin_subscriptions']->do_manual_upgrade_screen(
															 $sub_upgrade['sub_id'], 
															 $cur_trans['subtrans_id'],
															 $this->ipsclass->lang['sc_upgrade_string']
														   );
	}
	
	
}


?>