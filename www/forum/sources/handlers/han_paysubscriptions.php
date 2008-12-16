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
|   > $Date: 2007-09-19 15:37:06 -0400 (Wed, 19 Sep 2007) $
|   > $Revision: 1107 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Glue between IPB and Payment modules
|   > Module written by Matt Mecham
|   > Date started: Wednesday 31st March 2005 (15:23)
|
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class han_paysubscriptions
{
	# Global
	var $ipsclass;
	var $class_gateway;
	var $email;
	
	# Gateway
	var $gateway;
	
	# Error
	var $error;
	
	# Processing
	var $member;
	var $new_sub;
	var $trans;
	var $upgrade;
	var $results;
	var $customsubs;
	var $day_to_seconds = array( 'd' => 86400,
								 'w' => 604800,
								 'm' => 2592000,
								 'y' => 31536000,
							   );
							   
	/*-------------------------------------------------------------------------*/
    // SET UP
    /*-------------------------------------------------------------------------*/
    
    function main_init()
    {
    	//--------------------------------------------
    	// Get currencies...
    	//--------------------------------------------
    	
    	$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'subscription_currency' ) );
    	$this->ipsclass->DB->exec_query();
    	
    	while ( $c = $this->ipsclass->DB->fetch_row() )
    	{
    		$this->all_currency[ $c['subcurrency_code'] ] = $c;
    		
    		if ( $c['subcurrency_default'] )
    		{
    			$this->def_currency = $c;
    		}
    	}
		
		if ( isset($this->ipsclass->input['currency']) AND $this->ipsclass->input['currency'] )
		{
			if ( is_array($this->all_currency[  $this->ipsclass->input['currency'] ]) )
			{
				$this->cho_currency = $this->all_currency[  $this->ipsclass->input['currency'] ];
			}
			else
			{
				$this->cho_currency = $this->def_currency;
			}
		}
		else
		{
			$this->cho_currency = $this->def_currency;
		}
    }
    
    /*-------------------------------------------------------------------------*/
    // INIT
    /*-------------------------------------------------------------------------*/
    
    function gateway_init()
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	$class = "";
    	
    	//-----------------------------------------
    	// Which class
    	//-----------------------------------------
    	
    	if ( file_exists( ROOT_PATH . 'sources/classes/paymentgateways/class_gw_' . $this->gateway . '.php' ) )
    	{
    		$class = 'class_gw_' . $this->gateway . '.php';
    	}
    	else
    	{
    		$this->error = 'no_gateway';
    		return;
    	}
    	
    	//-----------------------------------------
    	// Define core
    	//-----------------------------------------
    	
    	define( 'GW_CORE_INIT', 1 );
    	
    	//-----------------------------------------
    	// Define Language & Function strings
    	//-----------------------------------------
    	
    	define( 'GW_URL_VALIDATE'  , $this->ipsclass->base_url.'act=paysubs&CODE=incoming&type=' . $this->gateway );
    	define( 'GW_URL_PAYDONE'   , $this->ipsclass->base_url.'act=paysubs&CODE=paydone&type='  . $this->gateway );
    	define( 'GW_URL_PAYCANCEL' , $this->ipsclass->base_url.'act=paysubs&CODE=paydone&type='  . $this->gateway );
    	
    	//-----------------------------------------
    	// Debug
    	//-----------------------------------------
    	
    	define( 'GW_DEBUG_MODE_ON', FALSE );
    	define( 'GW_TEST_MODE_ON' , FALSE ); // MAKE SURE THIS IS OFF WHEN IN LIVE USE!!
    	
		//-----------------------------------------
		// Load classes
		//-----------------------------------------
		
		require_once( ROOT_PATH . 'sources/classes/paymentgateways/class_gw_core.php' );
		require_once( ROOT_PATH . 'sources/classes/paymentgateways/'.$class );
		
		$this->class_gateway           =  new class_gw_module();
		$this->class_gateway->ipsclass =& $this->ipsclass;
		
		//-----------------------------------------
		// Init class
		//-----------------------------------------
		
        $this->class_gateway->main_init();
    }
    
    /*-------------------------------------------------------------------------*/
  	// SHOW UPGRADE PAYMENT SCREEN
  	/*-------------------------------------------------------------------------*/
  	
  	function show_upgrade_payment_screen($sub_current, $sub_upgrade, $pay_method, $extra)
	{
		//---------------------------------------
		// Check we can do upgrades
		//---------------------------------------
	
		if ( $this->class_gateway->can_do_upgrades != 1 )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'subs_no_upgrade' ) );
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
		
		$balance  = ($sub_upgrade['sub_cost'] * $this->all_currency[ $pay_method['submethod_use_currency'] ]['subcurrency_exchange']) - $cur_trans['subtrans_paid'];
		
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
															  sprintf( "%.2f", $balance * $this->cho_currency['subcurrency_exchange'] ).' '.$this->cho_currency['subcurrency_code'],
															  $new_date
															);
		
		//---------------------------------------
		// Time left to run
		//---------------------------------------
		
		if ( $sub_current['sub_unit'] != 'x' )
		{
			$time_left_to_run = ($cur_trans['subtrans_end_date'] + $sub_upgrade['sub_length']) - time();
			$time_left_to_run = ceil($time_left_to_run / 86400);
			$time_left_units  = 'D';
			
			if ( $time_left_to_run < 1 )
			{
				$time_left_to_run = 1;
			}
			else if ( $time_left_to_run > 30 )
			{
				$time_left_units = 'M';
				
				$time_left_to_run = ceil($time_left_to_run / 30);
			}
		}
		else
		{
			$time_left_to_run = $sub_upgrade['sub_length'];
			$time_left_units  = $sub_upgrade['sub_unit'];
		}
		
		if ( $extra['subextra_recurring'] == 1 AND $this->class_gateway->can_do_recurring_billing == 1 )
		{
			//---------------------------------------
			// Generate form fields
			//---------------------------------------
				
			$form_fields = $this->class_gateway->gw_generate_hidden_fields_upgrade_recurring( array('currency_code'    => $pay_method['submethod_use_currency'],
																									'member_unique_id' => $this->ipsclass->member['id'],
																									'member_name'      => $this->ipsclass->member['members_display_name'],
																									'member_email'     => $this->ipsclass->member['email'],
																									'package_cost'     => sprintf( "%.2f", $sub_upgrade['sub_cost'] * $this->all_currency[ $pay_method['submethod_use_currency'] ]['subcurrency_exchange'] ),
																									'package_id'       => $sub_upgrade['sub_id'],
																									'package_title'    => $sub_upgrade['sub_title'],
																									'duration_int'     => $sub_upgrade['sub_length'],
																									'duration_unit'    => strtoupper($sub_upgrade['sub_unit']),
																									'company_email'    => $pay_method['submethod_email'],
																									'ttr_int'          => $time_left_to_run,
																									'ttr_unit'         => $time_left_units,
																									'ttr_balance'      => sprintf( "%.2f", $balance ),
																									'ttr_package_id'   => $cur_trans['subtrans_sub_id'],
																									'vendor_id'        => $pay_method['submethod_sid'],
																									'product_id'       => $extra['subextra_product_id'],
																									'extra_1'          => $pay_method['submethod_custom_1'],
																									'extra_2'          => $pay_method['submethod_custom_2'],
																									'extra_3'          => $pay_method['submethod_custom_3'],
																									'extra_4'          => $pay_method['submethod_custom_4'],
																									'extra_5'          => $pay_method['submethod_custom_5'] ) );
			
			return $this->ipsclass->compiled_templates['skin_subscriptions']->do_generic_payscreen_with_button( array( 'formaction'   => $this->class_gateway->gw_generate_upgrade_recurring_form_action(),
																													   'formfields'   => $form_fields,
																													   'button'       => $this->class_gateway->gw_generate_purchase_button(),
																													   'lang_title'   => $this->ipsclass->lang['sc_complete'],
																													   'lang_explain' => $this->ipsclass->lang['sc_upgrade_explain'],
																													   'lang_desc'    => $this->ipsclass->lang['sc_upgrade_string'],
																													   'lang_extra'   => $this->ipsclass->lang['gw_' . $this->gateway ] ) );
		}
		else
		{
			//---------------------------------------
			// Generate form fields: NORMAL
			//---------------------------------------
				
			$form_fields = $this->class_gateway->gw_generate_hidden_fields_upgrade( array('currency_code'    => $pay_method['submethod_use_currency'],
																						  'member_unique_id' => $this->ipsclass->member['id'],
																						  'member_name'      => $this->ipsclass->member['members_display_name'],
																						  'member_email'     => $this->ipsclass->member['email'],
																						  'package_cost'     => sprintf( "%.2f", $sub_upgrade['sub_cost'] * $this->all_currency[ $pay_method['submethod_use_currency'] ]['subcurrency_exchange'] ),
																						  'package_id'       => $sub_upgrade['sub_id'],
																						  'package_title'    => $sub_upgrade['sub_title'],
																						  'duration_int'     => $sub_upgrade['sub_length'],
																						  'duration_unit'    => strtoupper($sub_upgrade['sub_unit']),
																						  'company_email'    => $pay_method['submethod_email'],
																						  'ttr_int'          => $time_left_to_run,
																						  'ttr_unit'         => $time_left_units,
																						  'ttr_balance'      => $balance,
																						  'ttr_package_id'   => $cur_trans['subtrans_sub_id'],
																						  'vendor_id'        => $pay_method['submethod_sid'],
																						  'product_id'       => $extra['subextra_product_id'],
																						  'extra_1'          => $pay_method['submethod_custom_1'],
																						  'extra_2'          => $pay_method['submethod_custom_2'],
																						  'extra_3'          => $pay_method['submethod_custom_3'],
																						  'extra_4'          => $pay_method['submethod_custom_4'],
																						  'extra_5'          => $pay_method['submethod_custom_5'] ) );
																				 
			
			return $this->ipsclass->compiled_templates['skin_subscriptions']->do_generic_payscreen_with_button( array( 'formaction'   => $this->class_gateway->gw_generate_upgrade_form_action(),
																													   'formfields'   => $form_fields,
																													   'button'       => $this->class_gateway->gw_generate_purchase_button(),
																													   'lang_title'   => $this->ipsclass->lang['sc_complete'],
																													   'lang_explain' => $this->ipsclass->lang['sc_upgrade_explain'],
																													   'lang_desc'    => $this->ipsclass->lang['sc_upgrade_string'],
																													   'lang_extra'   => $this->ipsclass->lang['gw_' . $this->gateway ] ) );
		}
		
	}
	
    /*-------------------------------------------------------------------------*/
    // SHOW NORMAL PAYMENT SCREEN
    /*-------------------------------------------------------------------------*/
  
  	function show_normal_payment_screen($sub_upgrade, $pay_method, $extra)
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
		
		$this->ipsclass->lang['sc_normal_string'] = sprintf( $this->ipsclass->lang['sc_normal_string'],
													   		 $sub_upgrade['sub_title'],
													   		 sprintf( "%.2f", $sub_upgrade['sub_cost'] * $this->cho_currency['subcurrency_exchange'] ).' '.$this->cho_currency['subcurrency_code']
													      );
		
		if ( $extra['subextra_recurring'] == 1 AND $this->class_gateway->can_do_recurring_billing == 1 AND $sub_upgrade['sub_unit'] != 'x' )
		{
			//---------------------------------------
			// Generate form fields
			//---------------------------------------
				
			$form_fields = $this->class_gateway->gw_generate_hidden_fields_normal_recurring( array('currency_code'    => $pay_method['submethod_use_currency'],
																								   'member_unique_id' => $this->ipsclass->member['id'],
																								   'member_name'      => $this->ipsclass->member['members_display_name'],
																								   'member_email'     => $this->ipsclass->member['email'],
																								   'package_cost'     => sprintf( "%.2f", $sub_upgrade['sub_cost'] * $this->all_currency[ $pay_method['submethod_use_currency'] ]['subcurrency_exchange'] ),
																								   'package_id'       => $sub_upgrade['sub_id'],
																								   'package_title'    => $sub_upgrade['sub_title'],
																								   'duration_int'     => $sub_upgrade['sub_length'],
																								   'duration_unit'    => strtoupper($sub_upgrade['sub_unit']),
																								   'company_email'    => $pay_method['submethod_email'],
																								   'ttr_int'          => '',
																								   'ttr_unit'         => '',
																								   'ttr_balance'      => '',
																								   'vendor_id'        => $pay_method['submethod_sid'],
																								   'product_id'       => $extra['subextra_product_id'],
																								   'extra_1'          => $pay_method['submethod_custom_1'],
																								   'extra_2'          => $pay_method['submethod_custom_2'],
																								   'extra_3'          => $pay_method['submethod_custom_3'],
																								   'extra_4'          => $pay_method['submethod_custom_4'],
																								   'extra_5'          => $pay_method['submethod_custom_5'] ) );
			
			return $this->ipsclass->compiled_templates['skin_subscriptions']->do_generic_payscreen_with_button( array( 'formaction'   => $this->class_gateway->gw_generate_normal_recurring_form_action(),
																													   'formfields'   => $form_fields,
																													   'button'       => $this->class_gateway->gw_generate_purchase_button(),
																													   'lang_title'   => $this->ipsclass->lang['sc_complete'],
																													   'lang_explain' => $this->ipsclass->lang['sc_upgrade_explain'],
																													   'lang_desc'    => $this->ipsclass->lang['sc_normal_string'],
																													   'lang_extra'   => $this->ipsclass->lang['gw_' . $this->gateway ] ) );
		}
		else
		{
			//---------------------------------------
			// Generate form fields: NORMAL
			//---------------------------------------
				
			$form_fields = $this->class_gateway->gw_generate_hidden_fields_normal( array('currency_code'    => $pay_method['submethod_use_currency'],
																						 'member_unique_id' => $this->ipsclass->member['id'],
																						 'member_name'      => $this->ipsclass->member['members_display_name'],
																						 'member_email'     => $this->ipsclass->member['email'],
																						 'package_cost'     => sprintf( "%.2f", $sub_upgrade['sub_cost'] * $this->all_currency[ $pay_method['submethod_use_currency'] ]['subcurrency_exchange'] ),
																						 'package_id'       => $sub_upgrade['sub_id'],
																						 'package_title'    => $sub_upgrade['sub_title'],
																						 'duration_int'     => $sub_upgrade['sub_length'],
																						 'duration_unit'    => strtoupper($sub_upgrade['sub_unit']),
																						 'company_email'    => $pay_method['submethod_email'],
																						 'ttr_package_id'   => $cur_trans['subtrans_sub_id'],
																						 'vendor_id'        => $pay_method['submethod_sid'],
																						 'product_id'       => $extra['subextra_product_id'],
																						 'extra_1'          => $pay_method['submethod_custom_1'],
																						 'extra_2'          => $pay_method['submethod_custom_2'],
																						 'extra_3'          => $pay_method['submethod_custom_3'],
																						 'extra_4'          => $pay_method['submethod_custom_4'],
																						 'extra_5'          => $pay_method['submethod_custom_5']
																				 )     );
			
			return $this->ipsclass->compiled_templates['skin_subscriptions']->do_generic_payscreen_with_button( array( 'formaction'   => $this->class_gateway->gw_generate_normal_form_action(),
																													   'formfields'   => $form_fields,
																													   'button'       => $this->class_gateway->gw_generate_purchase_button(),
																													   'lang_title'   => $this->ipsclass->lang['sc_complete'],
																													   'lang_explain' => $this->ipsclass->lang['sc_upgrade_explain'],
																													   'lang_desc'    => $this->ipsclass->lang['sc_normal_string'],
																													   'lang_extra'   => $this->ipsclass->lang['gw_' . $this->gateway ] ) );
		}
  	}
  	
  	/*-------------------------------------------------------------------------*/
	// Validate the payment
	/*-------------------------------------------------------------------------*/
	
	function validate_payment( $pay_method )
	{
		//--------------------------------------
		// INIT
		//--------------------------------------
		
		$this->results = array();
		$this->update  = array( 'subtrans_method' => $this->class_gateway->i_am );
		$this->member  = array();
		
		//--------------------------------------
		// Are we allowing auto manipulation?
		//--------------------------------------
		
		if ( $pay_method['submethod_is_auto'] != 1 )
		{
			$this->do_log("{$this->class_gateway->i_am}: Tried to return validate but failed: ACP settings have auto validate switched off");
			
			if ( $this->class_gateway->no_postback )
			{
				$this->ipsclass->boink_it( GW_URL_PAYDONE );
			}
			else
			{
				$this->class_gateway->core_print_status_message();
			}
			
			exit();
		}
		
		//--------------------------------------
		// Test POST data
		//--------------------------------------
		
		if ( empty( $_REQUEST ) )
		{
			$this->do_log("{$this->class_gateway->i_am}: Tried to return validate but failed: REQUEST DATA EMPTY");
			
			if ( $this->class_gateway->no_postback )
			{
				$this->ipsclass->boink_it( GW_URL_PAYDONE );
			}
			else
			{
				$this->class_gateway->core_print_status_message();
			}
			
			exit();
		}
		
		//--------------------------------------
		// Get results from class
		//--------------------------------------
		
		$this->results = $this->class_gateway->gw_validate_payment( array( 'vendor_id'     => $pay_method['submethod_sid'],
																		   'company_email' => $pay_method['submethod_email'],
																		   'extra_1'       => $pay_method['submethod_custom_1'],
																		   'extra_2'       => $pay_method['submethod_custom_2'],
																		   'extra_3'       => $pay_method['submethod_custom_3'],
																		   'extra_4'       => $pay_method['submethod_custom_4'],
																		   'extra_5'       => $pay_method['submethod_custom_5'] ) );
		
		//--------------------------------------
		// VERIFIED?
		//--------------------------------------
		
		if ( ! $this->results['verified'] )
		{
			if ( ! GW_TEST_MODE_ON )
			{ 
				$this->do_log("{$this->class_gateway->i_am}: UPGRADE - NOT VERIFIED " . $this->class_gateway->error );
				
				if ( $this->class_gateway->no_postback )
				{
					$this->ipsclass->boink_it( GW_URL_PAYDONE );
				}
				else
				{
					$this->class_gateway->core_print_status_message();
				}
				
				exit();
			}
		}
		
		//--------------------------------------
		// Check for member id
		//--------------------------------------
		
		if ( $this->results['member_unique_id'] > 0 )
		{
			$this->member = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'members', 'where' => 'id='.intval($this->results['member_unique_id']) ) );
		}
		
		if ( GW_TEST_MODE_ON )
		{
			print "<pre>";
			print_r( $this->results );
			print "</pre>";
		}
		
		//--------------------------------------
		// Got a member?
		//--------------------------------------
		
		if ( ! $this->member['id'] )
		{
			$this->do_log("{$this->class_gateway->i_am}: Could not locate a member id to upgrade");
			
			if ( $this->class_gateway->no_postback )
			{
				$this->ipsclass->boink_it( GW_URL_PAYDONE );
			}
			else
			{
				$this->class_gateway->core_print_status_message();
			}
			
			exit();
		}
		
		//--------------------------------------
		// Got a new subs package?
		//--------------------------------------
		
		$this->new_sub = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'subscriptions', 'where' => 'sub_id='.intval($this->results['purchase_package_id']) ) );
		
		if ( ! $this->new_sub['sub_id'] )
		{
			$this->do_log("{$this->class_gateway->i_am}: Tried to return validate but failed: No start sub package found");
			
			if ( $this->class_gateway->no_postback )
			{
				$this->ipsclass->boink_it( GW_URL_PAYDONE );
			}
			else
			{
				$this->class_gateway->core_print_status_message();
			}
		}
		
		//--------------------------------------
		// Check for txn_id - if already used, this
		// is a dupe from a repeated form submit
		//--------------------------------------
		
		$this->trans  = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'subtrans_id',
																		  'from'   => 'subscription_trans',
																		  'where'  => "subtrans_trxid='".addslashes($this->results['transaction_id'])."'" ) );
		
		if ( $this->trans['subtrans_id'] )
		{
			//--------------------------------------
			// Is this a reversal?
			//--------------------------------------
			
			if ( $this->results['payment_status'] == 'REFUND' OR $this->results['payment_status'] == 'CANCEL' )
			{
				//-----------------------------
				// Update trans for the refund
				//-----------------------------
				
				$this->ipsclass->DB->do_update( 'subscription_trans', array( 'subtrans_state' => "failed" ), "subtrans_id={$this->trans['subtrans_id']}" );
				
				$this->do_failed_member($this->new_sub, $this->member, $this->trans['subtrans_id']);
				
				//-----------------------------
				// Write Log
				//-----------------------------
				
				$this->do_log("{$this->class_gateway->i_am}: Reversal / Subscription cancellation completed");
				
				if ( $this->class_gateway->no_postback )
				{
					$this->ipsclass->boink_it( GW_URL_PAYDONE );
				}
				else
				{
					$this->class_gateway->core_print_status_message();
				}
				exit();
			}
			else
			{
				$this->do_log("{$this->class_gateway->i_am}: Duplicate transaction ID - failing and exiting transaction");
				
				
				if ( $this->class_gateway->no_postback )
				{
					$this->ipsclass->boink_it( GW_URL_PAYDONE );
				}
				else
				{
					$this->class_gateway->core_print_status_message();
				}
				exit();
			}
		}
			
		//--------------------------------------
		// UPGRADE
		//--------------------------------------
		
		if ( $this->results['current_package_id'] > 0 )
		{
			//--------------------------------------
			// Get current details
			//--------------------------------------
			
			$this->cur_details = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																				   'from'   => 'subscription_trans',
																				   'where'  => 'subtrans_member_id='.intval($this->member['id'])
																				             . ' AND subtrans_state="paid" AND subtrans_sub_id='.$this->results['current_package_id']) );
			
			//--------------------------------------
			// Check
			//--------------------------------------
			
			if ( ! $this->cur_details['subtrans_id'] )
			{
				$this->do_log("{$this->class_gateway->i_am}: Tried to return validate but failed: Upgrade, but no original package found");
			}
			
			if ( $this->results['currency_code'] != $pay_method['sub_use_currency'] )
			{
				$this->do_log("{$this->class_gateway->i_am}: Tried to return validate but failed: Wrong currency ({$this->results['currency_code']})");
			}
			
			//--------------------------------------
			// What to do...
			//--------------------------------------
			
			if ( $this->results['payment_status'] == 'RECURRING' )
			{
				$this->_process_recurring_upgrade( $pay_method );					
			}
			else if ( $this->results['payment_status'] == 'ONEOFF' )
			{
				$this->_process_normal_upgrade( $pay_method );
			}
			else
			{
				$this->do_log("{$this->class_gateway->i_am}: Subscription Start. Unknown trx_type: {$_POST['txn_type']}");
			}
		}
		
		//--------------------------------------
		// NON-UPGRADE
		//--------------------------------------
			
		else
		{
			if ( $this->results['currency_code'] != $pay_method['submethod_use_currency'] )
			{
				$this->do_log("{$this->class_gateway->i_am}: Tried to return validate but failed: Wrong currency ($this->results['currency_code'])");
			}
				
			if ( $this->results['payment_status'] == 'RECURRING' )
			{
				$this->_process_recurring( $pay_method );				
			}
			else if ( $this->results['payment_status'] == 'ONEOFF' )
			{
				$this->_process_normal( $pay_method );	
			}
		}
		
		if ( $this->class_gateway->no_postback )
		{
			$this->ipsclass->boink_it( GW_URL_PAYDONE );
		}
		else
		{
			$this->class_gateway->core_print_status_message();
		}
		
		exit();
	}
	
  	/*-------------------------------------------------------------------------*/
  	// Process normal upgrade
  	/*-------------------------------------------------------------------------*/
  	
  	function _process_normal_upgrade( $pay_method )
  	{
  		//--------------------------------------
		// SUBSCRIPTION
		//--------------------------------------
		
		$balance                   = ( $this->new_sub['sub_cost'] * $this->all_currency[ $pay_method['submethod_use_currency'] ]['subcurrency_exchange'] ) - $this->cur_details['subtrans_paid'];
		$this->new_sub['sub_cost'] = $this->new_sub['sub_cost']   * $this->all_currency[ $pay_method['submethod_use_currency'] ]['subcurrency_exchange'];
		
		$results = $this->class_gateway->gw_do_normal_payment_check(  sprintf( "%.2f", $balance ),  sprintf( "%.2f", $this->new_sub['sub_cost'] ), $this->cur_details['subtrans_id'] );
		
		if ( $this->class_gateway->error )
		{
			//--------------------------------------
			// Log and exit
			//--------------------------------------
			
			$this->do_log( "{$this->class_gateway->i_am}: ".$this->class_gateway->error );
			exit();
		}
		
		$this->existing_sub = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																				   'from'   => 'subscriptions',
																				   'where'  => 'sub_id='.intval($this->cur_details['subtrans_sub_id'])
																		)		);
		
  		//--------------------------------------
		// Update subscription
		//--------------------------------------
		
		$this->update['subtrans_paid']     = $results['amount_paid'] * $this->def_currency['subcurrency_exchange'];
		$this->update['subtrans_trxid']    = $this->results['transaction_id'];
		$this->update['subtrans_sub_id']   = $this->new_sub['sub_id'];
		$this->update['subtrans_subscrid'] = $this->results['subscription_id'];
  		$this->update['subtrans_state']    = strtolower( $results['state'] );
  		
		//--------------------------------------
		// Deal with lifetime subs
		//--------------------------------------
		
		if ( $this->new_sub['sub_unit'] == 'x' )
		{
			$this->update['subtrans_end_date'] = 9999999999;
		}
		
		//--------------------------------------
		// Else increase their sub
		//--------------------------------------		
		
		else
		{
			$this->update['subtrans_end_date'] = $this->cur_details['subtrans_end_date'] + ( $this->new_sub['sub_length'] * $this->day_to_seconds[ $this->new_sub['sub_unit'] ] );
			$this->update['subtrans_end_date'] -= ($this->existing_sub['sub_length'] * $this->day_to_seconds[ $this->existing_sub['sub_unit'] ] );	
		}
		
		//--------------------------------------
		// Update DB
		//--------------------------------------
		
		if ( $this->update['subtrans_paid'] > 0.00 )
		{
			$this->update['subtrans_cumulative'] = $this->cur_details['subtrans_cumulative'] + $this->update['subtrans_paid'];
		}
		
		$this->ipsclass->DB->do_update( 'subscription_trans', $this->update, "subtrans_id=".$this->cur_details['subtrans_id'] );
  		
		//--------------------------------------
		// UPDATE MEMBER
		//--------------------------------------
		
		if ( $this->update['subtrans_state'] == 'paid' )
		{
			$this->do_paid_member($this->new_sub, $this->member, $this->cur_details['subtrans_id']);
		}
		else
		{
			$this->do_failed_member($this->new_sub, $this->member, $this->cur_details['subtrans_id']);
		}
		
		$this->do_log("{$this->class_gateway->i_am}: Subscription upgrade (one off payment). Set trans_id {$this->cur_details['subtrans_id']} to {$this->update['subtrans_state']}, paid {$this->update['subtrans_paid']}");
	}			
				
  	/*-------------------------------------------------------------------------*/
  	// Process recurring upgrade
  	/*-------------------------------------------------------------------------*/
  	
  	function _process_recurring_upgrade( $pay_method )
  	{
  		//--------------------------------------
		// SUBSCRIPTION
		//--------------------------------------
		
		$balance                   = ( $this->new_sub['sub_cost'] * $this->all_currency[ $pay_method['submethod_use_currency'] ]['subcurrency_exchange'] ) - $this->cur_details['subtrans_paid'];
		$this->new_sub['sub_cost'] = $this->new_sub['sub_cost']   * $this->all_currency[ $pay_method['submethod_use_currency'] ]['subcurrency_exchange'];
		
		$results = $this->class_gateway->gw_do_recurring_payment_check(  sprintf( "%.2f", $balance ),  sprintf( "%.2f", $this->new_sub['sub_cost'] ) );
		
		if ( $this->class_gateway->error )
		{
			//--------------------------------------
			// Log and exit
			//--------------------------------------
			
			$this->do_log( "{$this->class_gateway->i_am}: ".$this->class_gateway->error );
			exit();
		}
		
		$this->existing_sub = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																				   'from'   => 'subscriptions',
																				   'where'  => 'sub_id='.intval($this->cur_details['subtrans_sub_id'])
																		)		);		
		
		//--------------------------------------
		// Update subscription
		//--------------------------------------
		
		$this->update['subtrans_paid']     = $results['amount_paid'] * $this->def_currency['subcurrency_exchange'];
		$this->update['subtrans_trxid']    = $this->results['transaction_id'];
		$this->update['subtrans_sub_id']   = $this->new_sub['sub_id'];
		$this->update['subtrans_subscrid'] = $this->results['subscription_id'];
		$this->update['subtrans_state']    = strtolower( $results['state'] );
		
		//--------------------------------------
		// Deal with lifetime subs
		//--------------------------------------
		
		if ( $this->new_sub['sub_unit'] == 'x' )
		{
			$this->update['subtrans_end_date'] = 9999999999;
		}
		
		//--------------------------------------
		// Else increase their sub
		//--------------------------------------		
		
		else
		{
			$this->update['subtrans_end_date'] = $this->cur_details['subtrans_end_date'] + ( $this->new_sub['sub_length'] * $this->day_to_seconds[ $this->new_sub['sub_unit'] ] );
			$this->update['subtrans_end_date'] -= ($this->existing_sub['sub_length'] * $this->day_to_seconds[ $this->existing_sub['sub_unit'] ] );	
		}		
		
		//--------------------------------------
		// Update DB
		//--------------------------------------
		
		if ( $this->update['subtrans_paid'] > 0.00 )
		{
			$this->update['subtrans_cumulative'] = $this->cur_details['subtrans_cumulative'] + $this->update['subtrans_paid'];
		}
		
		$this->ipsclass->DB->do_update( 'subscription_trans', $this->update, "subtrans_id=".$this->cur_details['subtrans_id'] );
		
		//--------------------------------------
		// UPDATE MEMBERS
		//--------------------------------------
		
		if ( $this->update['subtrans_state'] == 'paid' )
		{
			$this->do_paid_member($this->new_sub, $this->member, $this->cur_details['subtrans_id']);
		}
		else
		{
			$this->do_failed_member($this->new_sub, $this->member, $this->cur_details['subtrans_id']);
		}
		
		$this->do_log("{$this->class_gateway->i_am}: Subscription upgrade. Set trans_id {$this->cur_details['subtrans_id']} to {$this->update['subtrans_state']}, paid {$this->update['subtrans_paid']}");
	}
	
	/*-------------------------------------------------------------------------*/
  	// Process normal NON UPGRADE
  	/*-------------------------------------------------------------------------*/
  	
  	function _process_normal( $pay_method )
  	{
  		//--------------------------------------
		// SUBSCRIPTION
		//--------------------------------------
		
		$balance                   = ( $this->new_sub['sub_cost'] * $this->all_currency[ $pay_method['submethod_use_currency'] ]['subcurrency_exchange'] ) - $this->cur_details['subtrans_paid'];
		$this->new_sub['sub_cost'] = $this->new_sub['sub_cost']   * $this->all_currency[ $pay_method['submethod_use_currency'] ]['subcurrency_exchange'];
		
		$results = $this->class_gateway->gw_do_normal_payment_check(  sprintf( "%.2f", $balance ),  sprintf( "%.2f", $this->new_sub['sub_cost'] ), $this->cur_details['subtrans_id'] );
		
		if ( $this->class_gateway->error )
		{
			//--------------------------------------
			// Log and exit
			//--------------------------------------
			
			$this->do_log( "{$this->class_gateway->i_am}: ".$this->class_gateway->error );
			exit();
		}
		
		//--------------------------------------
		// Does the verification key match?
		//--------------------------------------
		
		if( $this->results['verification'] != md5( intval($this->member['id']) . $this->results['purchase_package_id'] . $this->ipsclass->vars['sql_pass'] ) )
		{
			$this->do_log( "{$this->class_gateway->i_am}: Unable to verify payment - verification key mismatch" );
			exit();
		}
		
		//--------------------------------------
		// Update subscription
		//--------------------------------------
		
		$old_group = ($this->new_sub[ 'sub_new_group' ] > 0) ? $this->member['mgroup'] : 0;
		
		$this->update['subtrans_paid']       = $results['amount_paid'] * $this->def_currency['subcurrency_exchange'];
		$this->update['subtrans_trxid']      = $this->results['transaction_id'];
		$this->update['subtrans_sub_id']     = $this->new_sub['sub_id'];
		$this->update['subtrans_subscrid']   = $this->results['subscription_id'];
		$this->update['subtrans_state']      = strtolower( $results['state'] );
		$this->update['subtrans_cumulative'] = $this->update['subtrans_paid'];
		$this->update['subtrans_member_id']  = $this->member['id'];
		$this->update['subtrans_old_group']  = $old_group;
		$this->update['subtrans_start_date'] = time();
	
		//--------------------------------------
		// Deal with lifetime subs
		//--------------------------------------
				
		if ( $this->new_sub['sub_unit'] == 'x' )
		{
			$this->update['subtrans_end_date'] = 9999999999;
		}
		else
		{
			$this->update['subtrans_end_date']   = time() + ( $this->new_sub['sub_length'] * $this->day_to_seconds[ $this->new_sub['sub_unit'] ] );
		}
				
		//--------------------------------------
		// Just updating a subscription
		//--------------------------------------
		
		if ( $this->results['current_package_id'] )
		{
			$subid = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'subscription_trans', 'where' => "subtrans_sub_id={$this->new_sub['sub_id']} and subtrans_state='pending'" ) );
			
			if ( $subid['subtrans_id'] )
			{
				//--------------------------------------
				// Okay, existing subscription we're updating, so...
				//--------------------------------------
				
				unset($this->update['subtrans_start_date']);
				unset($this->update['subtrans_end_date']);
				unset($this->update['subtrans_old_group']);
				unset($this->update['subtrans_cumulative']);
				
				$this->ipsclass->DB->do_update( 'subscription_trans', $this->update, "subtrans_subscrid='".addslashes($this->update['subtrans_subscrid'])."'" );
			}
		}
		else
		{
			$this->ipsclass->DB->do_insert( 'subscription_trans', $this->update );
			
			$newid = $this->ipsclass->DB->get_insert_id();
			
			//--------------------------------------
			// Mark all old subs as dead
			//--------------------------------------
			
			$this->ipsclass->DB->do_update( 'subscription_trans', array( 'subtrans_state' => 'dead' ), "subtrans_state='paid' AND subtrans_member_id={$this->member['id']} AND subtrans_id != $newid" );
		}
				
		//--------------------------------------
		// UPDATE MEMBERS
		//--------------------------------------
			
		if ( $this->update['subtrans_state'] == 'paid' )
		{
			$this->do_paid_member($this->new_sub, $this->member, $subid['subtrans_id']);
			$this->do_log("{$this->class_gateway->i_am}: Subscription started (non recurring). Set trans_id {$this->cur_details['subtrans_id']} to {$this->update['subtrans_state']}, paid {$this->update['subtrans_paid']}");
		}
		else
		{
			$this->do_failed_member($this->new_sub, $this->member, $subid['subtrans_id']);
			$this->do_log("{$this->class_gateway->i_am}: Subscription FAILED (not set to pod) (non recurring). Set trans_id {$this->cur_details['subtrans_id']} to {$this->update['subtrans_state']}, paid {$this->update['subtrans_paid']}");

		}
	}

	/*-------------------------------------------------------------------------*/
  	// Process recurring NON UPGRADE
  	/*-------------------------------------------------------------------------*/
  	
  	function _process_recurring( $pay_method )
  	{
  		//--------------------------------------
		// SUBSCRIPTION
		//--------------------------------------
		
		$balance                   = ( $this->new_sub['sub_cost'] * $this->all_currency[ $pay_method['submethod_use_currency'] ]['subcurrency_exchange'] ) - $this->cur_details['subtrans_paid'];
		$this->new_sub['sub_cost'] = $this->new_sub['sub_cost']   * $this->all_currency[ $pay_method['submethod_use_currency'] ]['subcurrency_exchange'];
		
		$results   = $this->class_gateway->gw_do_recurring_payment_check(  sprintf( "%.2f", $balance ),  sprintf( "%.2f", $this->new_sub['sub_cost'] ) );
		
		if ( $this->class_gateway->error )
		{
			//--------------------------------------
			// Log and exit
			//--------------------------------------
			
			$this->do_log( "{$this->class_gateway->i_am}: ".$this->class_gateway->error );
			exit();
		}

		//--------------------------------------
		// Does the verification key match?
		//--------------------------------------
		
		if( $this->results['verification'] != md5( intval($this->member['id']) . $this->results['purchase_package_id'] . $this->ipsclass->vars['sql_pass'] ) )
		{
			$this->do_log( "{$this->class_gateway->i_am}: Unable to verify payment - verification key mismatch" );
			exit();
		}
		
		//--------------------------------------
		// Update subscription
		//--------------------------------------
		
		$this->update['subtrans_paid']       = $results['amount_paid'] * $this->def_currency['subcurrency_exchange'];
		$this->update['subtrans_trxid']      = $this->results['transaction_id'];
		$this->update['subtrans_sub_id']     = $this->new_sub['sub_id'];
		$this->update['subtrans_subscrid']   = $this->results['subscription_id'];
		$this->update['subtrans_state']      = strtolower( $results['state'] );
		$this->update['subtrans_cumulative'] = $this->update['subtrans_paid'];
		$this->update['subtrans_member_id']  = $this->member['id'];
		$this->update['subtrans_old_group']  = $this->member['mgroup'];
		$this->update['subtrans_start_date'] = time();
		
		//--------------------------------------
		// Deal with lifetime subs
		//--------------------------------------
				
		if ( $this->new_sub['sub_unit'] == 'x' )
		{
			$this->update['subtrans_end_date'] = 9999999999;
		}
		else
		{
			$this->update['subtrans_end_date']   = time() + ( $this->new_sub['sub_length'] * $this->day_to_seconds[ $this->new_sub['sub_unit'] ] );
		}
				
		//--------------------------------------
		// Just updating a subscription
		//--------------------------------------
		
		if ( $this->results['current_package_id'] )
		{
			$subid = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'subscription_trans', 'where' => "subtrans_subscrid='".addslashes($this->update['subtrans_subscrid'])."'" ) );
			
			if ( $subid['subtrans_id'] )
			{
				//--------------------------------------
				// Okay, existing subscription we're updating, so...
				//--------------------------------------
				
				unset($this->update['subtrans_start_date']);
				unset($this->update['subtrans_end_date']);
				unset($this->update['subtrans_old_group']);
				unset($this->update['subtrans_cumulative']);
				
				$this->ipsclass->DB->do_update( 'subscription_trans', $this->update, "subtrans_subscrid='".addslashes($this->update['subtrans_subscrid'])."'" );
			}
		}
		else
		{
			$this->ipsclass->DB->do_insert( 'subscription_trans', $this->update );
			
			$newid = $this->ipsclass->DB->get_insert_id();
			
			//--------------------------------------
			// Mark all old subs as dead
			//--------------------------------------
			
			$this->ipsclass->DB->do_update( 'subscription_trans', array( 'subtrans_state' => 'dead' ), "subtrans_state='paid' AND subtrans_member_id={$this->member['id']} AND subtrans_id != $newid" );
		}
		
		//--------------------------------------
		// UPDATE MEMBERS
		//--------------------------------------
		
		if ( $this->update['subtrans_state'] == 'paid' )
		{
			$this->do_paid_member($this->new_sub, $this->member, $subid['subtrans_id']);
			$this->do_log("{$this->class_gateway->i_am}: Subscription started. Set trans_id {$this->cur_details['subtrans_id']} to {$this->update['subtrans_state']}, paid {$this->update['subtrans_paid']}");
		}
		else
		{
			$this->do_failed_member($this->new_sub, $this->member, $subid['subtrans_id']);
			$this->do_log("{$this->class_gateway->i_am}: Subscription FAILED. Set trans_id {$this->cur_details['subtrans_id']} to {$this->update['subtrans_state']}, paid {$this->update['subtrans_paid']}");
		}
	}
  	
  	/*-------------------------------------------------------------------------*/
	// PAID MEMBER UPGRADE YEAH BABY
	/*-------------------------------------------------------------------------*/
    
    function do_paid_member($new_sub, $member, $cur_trx_id="")
    {
    	//--------------------------------------
    	// INIT
    	//--------------------------------------
    	
    	$end_date       = 0;
    	$email_end_date = 0;
    	
    	//--------------------------------------
    	// Make sure we have enough member info
    	//--------------------------------------
    	
    	if ( ! $member['email'] )
    	{
    		$member = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'members', 'where' => 'id='.intval($member['id']) ) );
    	}
    	
    	define( 'IPB_CALLED', 1 );
    	
    	//--------------------------------------
    	// Lifetime...
    	//--------------------------------------
    	
    	if ( $new_sub['sub_unit'] == 'x' )
    	{
    		$end_date       = 9999999999;
    		$email_end_date = $this->ipsclass->lang['no_expire'];
    	}
    	else
    	{
    		if ( $cur_trx_id )
    		{
    			//--------------------------------------
    			// Upgrading...
    			//--------------------------------------
    			
    			$end_date       = 'sub_end';
    			$email_end_date = $this->ipsclass->get_date( $member['sub_end'], 'DATE' );
    		}
    		else
    		{
    			//--------------------------------------
    			// New...
    			//--------------------------------------
    			
    			$end_date       = time() + ( $new_sub['sub_length'] * $this->class_gateway->day_to_seconds[ $new_sub['sub_unit'] ] );
    			$email_end_date = $this->ipsclass->get_date( $end_date, 'DATE' );
    		}
    	}
    	
    	$update_fields 				= array();
    	$update_fields['sub_end']	= $end_date;
    	
    	if ( $new_sub['sub_new_group'] AND !$cur_trx_id )
    	{
    		$update_fields['mgroup'] = $new_sub['sub_new_group'];
    	}
    	
    	$this->ipsclass->DB->do_update( "members", $update_fields, "id=".$member['id'] );
    	
    	//--------------------------------------
    	// Running Custom code?
    	//--------------------------------------
    	
    	$name = preg_replace( "/[^a-zA-Z0-9\-\_]/", "" , $new_sub['sub_run_module'] );
    	
    	if ( $name != "" )
    	{
			if ( @file_exists( ROOT_PATH . 'sources/classes/paymentgateways/custom/cus_'.$name.'.php' ) )
			{
				require_once( ROOT_PATH . 'sources/classes/paymentgateways/custom/cus_'.$name.'.php' );
				
				$this->customsubs = new customsubs();
				
				$this->customsubs->subs_paid($new_sub, $member, $cur_trx_id);
			}
		}
		
		//--------------------------------------
		// Running IPB custom code?
		//--------------------------------------
		
		if ( USE_MODULES == 1 )
		{
			require ROOT_PATH."modules/ipb_member_sync.php";
			
			$this->modules = new ipb_member_sync();
			
			$this->modules->register_class($this);
			$this->modules->on_group_change($member['id'], $new_sub['sub_new_group']);
		}
		
		//--------------------------------------
		// Send an email?
		//--------------------------------------
        
        require_once( ROOT_PATH."sources/classes/class_email.php" );
		$this->email = new emailer();
        $this->email->ipsclass =& $this->ipsclass;
        $this->email->email_init();
        
        $this->email->get_template("new_subscription");
		$this->email->build_message( array(
											'PACKAGE'  => $new_sub['sub_title'],
											'EXPIRES'  => $email_end_date,
											'LINK'     => $this->ipsclass->vars['board_url'].'/index.'.$this->ipsclass->vars['php_ext'].'?act=paysubs&CODE=index',
								   )     );
		
		$this->email->to = trim( $member['email'] );
		$this->email->send_mail();
    }
    
    /*-------------------------------------------------------------------------*/
	// FAILED MEMBER UPGRADE YEAH BABY
	/*-------------------------------------------------------------------------*/
    
    function do_failed_member($new_sub, $member, $cur_trx_id="")
    {
    	define( 'IPB_CALLED', 1 );
    	
    	$update_fields 				= array();
    	$update_fields['sub_end']	= 0;
    	
    	// We want to move them back to the member group if the payment
    	// failed, but not if they cancelled a subscription - bug 2008
    	
    	if ( $cur_trx_id AND !$this->results['payment_status'] == 'CANCEL' )
    	{
    		$this->ipsclass->DB->cache_add_query( 'mod_failed_member', array( 'cur_trx_id' => $cur_trx_id ), 'sql_subsm_queries' );
			$this->ipsclass->DB->cache_exec_query();
    		
    		$r = $this->ipsclass->DB->fetch_row();
    		
    		$mgroup = $r['g_id'] ? $r['g_id'] : $this->ipsclass->vars['member_group'];
    		
    		$update_fields['mgroup'] = $mgroup;
    	}
    	
    	$this->ipsclass->DB->do_update( "members", $update_fields, "id=".$member['id'] );
    	
    	//--------------------------------------
    	// Running Custom code?
    	//--------------------------------------
    	
    	$name = preg_replace( "/[^a-zA-Z0-9\-\_]/", "" , $new_sub['sub_run_module'] );
    	
    	if ( $name != "" )
    	{
			if ( @file_exists( ROOT_PATH . 'sources/classes/paymentgateways/custom/cus_'.$name.'.php' ) )
			{
				require_once( ROOT_PATH . 'sources/classes/paymentgateways/custom/cus_'.$name.'.php' );
				
				$this->customsubs = new customsubs();
				
				$this->customsubs->subs_failed($new_sub, $member, $cur_trx_id);
			}
		}
		
		//--------------------------------------
		// Running IPB custom code?
		//--------------------------------------
		
		if ( USE_MODULES == 1 )
		{
			require ROOT_PATH."modules/ipb_member_sync.php";
			
			$this->modules = new ipb_member_sync();
			
			$this->modules->register_class($this);
			$this->modules->on_group_change($member['id'], $mgroup);
		}
    }
    
    /*-------------------------------------------------------------------------*/
	// Captains log: Unix date: 1002343439
	/*-------------------------------------------------------------------------*/
	
	function do_log($msg)
	{
		$extra = "";
			
		foreach( $_POST as $k => $v )
		{
			if ( $k == 'ccnum' )
			{
				$v = 'xxxx xxxx xxxx xxxx';
			}
			else if ( $k == 'ccid' )
			{
				$v = 'xxx';
			}
			
			$extra .= "\n$k  =  $v;";
		}
		
		if ( is_array( $_GET ) )
		{
			foreach( $_GET as $k => $v )
			{
				if ( $k == 'ccnum' )
				{
					$v = 'xxxx xxxx xxxx xxxx';
				}
				else if ( $k == 'ccid' )
				{
					$v = 'xxx';
				}
				
				$extra .= "\n$k  =  $v;";
			}
		}
		
		$extra = htmlentities( strip_tags( str_replace( '\\', '', $extra ) ) );
		
		//--------------------------------------
		// Add to DB
		//--------------------------------------
		
		$this->ipsclass->DB->do_insert( 'subscription_logs', array(
																	'sublog_date'      => time(),
																	'sublog_data'      => $msg,
																	'sublog_ipaddress' => $this->ip_address,
																	'sublog_postdata'  => $extra ) );
																	
		//--------------------------------------
		// Debug?
		//--------------------------------------
		
		$this->class_gateway->_write_debug_message( "MSG:\n$msg\nPost contents\n$extra\n" );
	}
	
}

?>