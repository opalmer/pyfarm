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
|
|   > Payment Gateway API: PAYPAL
|   > Module written by Matt Mecham
|   > Date started: 31st March 2005 (14:45)
|
|
+--------------------------------------------------------------------------
*/
		
if ( ! defined( 'GW_CORE_INIT' ) )
{
	print "You cannot access this module in this manner";
	exit();
}

//--------------------------------------------------------------------------
// DEFINITIONS EXPECTED AT THIS POINT
//--------------------------------------------------------------------------
// GW_URL_VALIDATE : The url for validating payment
// GW_URL_PAYDONE  : The url that the gatways returns the viewer to after
//                 : payment processed successfully
// GW_URL_PAYCANCEL: The url that the gatways returns the viewer to after
//                 : payment processed unsuccessfully or when cancelled
//--------------------------------------------------------------------------
// ITEM ARRAY
//--------------------------------------------------------------------------
// 'currency_code'    => Currency code,
// 'member_unique_id' => member's ID,
// 'member_name'      => member's NAME,
// 'member_email'     => member's EMAIL,
// 'package_cost'     => Requested package cost
// 'package_id'       => Requested package ID
// 'package_title'    => Requested package title
// 'duration_int'     => Requested package duration int  (ie: 12)
// 'duration_unit'    => Requested package duration unit (ie: m,d,y,w) [ month, day, year, week ]
// 'company_email'    => Company's email address
// 'ttr_int'          => Time to run (Time left on current package) integar (ie 3)
// 'ttr_unit'         => Time to run (Time left on current package) unit (ie w)
// 'ttr_balance'      => Time to run (Balance left on current package)
// 'ttr_package_id'   => Current package id (used for upgrading)
//--------------------------------------------------------------------------

class class_gw_module EXTENDS class_gateway
{

	# Global
	var $ipsclass;
	
	# Identify
	var $i_am = 'test';
	
	var $can_do_recurring_billing = 0;
	var $can_do_upgrades          = 1;
	
	/*-------------------------------------------------------------------------*/
	// INIT
	/*-------------------------------------------------------------------------*/
	
	function main_init()
	{
	}
	
	/*-------------------------------------------------------------------------*/
	// Generate hidden fields [ Recurring, normal screen ]
	/*-------------------------------------------------------------------------*/
	
	function gw_generate_hidden_fields_normal_recurring( $items=array() )
	{
		// Not available for this gateway
		
		return $this->core_compile_hidden_fields();
	}
	
	/*-------------------------------------------------------------------------*/
	// Generate hidden fields [ Recurring, upgrade screen ]
	/*-------------------------------------------------------------------------*/
	
	function gw_generate_hidden_fields_upgrade_recurring( $items=array() )
	{
		// Not available for this gateway
		
		return $this->core_compile_hidden_fields();
	}
	
	/*-------------------------------------------------------------------------*/
	// Generate hidden fields [ normal screen ]
	/*-------------------------------------------------------------------------*/
	
	function gw_generate_hidden_fields_normal( $items=array() )
	{
		$this->core_add_hidden_field( "ordernumber"  , $items['package_id'].'x'.$items['member_unique_id'].'x0' );
		$this->core_add_hidden_field( "amount"       , $items['package_cost'] );
		$this->core_add_hidden_field( "desc"         , $items['package_title'] );
		$this->core_add_hidden_field( "email"        , $items['company_email'] );
		$this->core_add_hidden_field( "From_email"   , $items['member_email'] );
		$this->core_add_hidden_field( "status"       , 'live' );
		$this->core_add_hidden_field( "responderurl" , GW_URL_VALIDATE  );
		$this->core_add_hidden_field( "returnurl"    , GW_URL_PAYDONE   );
		
		$this->core_add_hidden_field( "verification" , md5( $items['member_unique_id'] . $items['package_id'] . $this->ipsclass->vars['sql_pass'] ) );
		
		return $this->core_compile_hidden_fields();
	}
	
	/*-------------------------------------------------------------------------*/
	// Generate hidden fields [ upgrade screen ]
	/*-------------------------------------------------------------------------*/
	
	function gw_generate_hidden_fields_upgrade( $items=array() )
	{
		$this->core_add_hidden_field( "ordernumber"     , $items['package_id'].'x'.$items['member_unique_id'].'x'.$items['ttr_package_id'] );
		$this->core_add_hidden_field( "amount"       , $items['package_cost'] );
		$this->core_add_hidden_field( "email"       , $items['company_email'] );
		$this->core_add_hidden_field( "From_email"   , $items['member_email'] );
		$this->core_add_hidden_field( "status"       , 'live' );
		$this->core_add_hidden_field( "responderurl" , GW_URL_VALIDATE  );
		$this->core_add_hidden_field( "returnurl"    , GW_URL_PAYDONE   );
		
		$this->core_add_hidden_field( "verification" , md5( $items['member_unique_id'] . $items['package_id'] . $this->ipsclass->vars['sql_pass'] ) );
		
		return $this->core_compile_hidden_fields();
	}
	
	/*-------------------------------------------------------------------------*/
	// Generate Purchase button
	/*-------------------------------------------------------------------------*/
	
	function gw_generate_purchase_button()
	{
		return '<input type="image" src="http://www.nochex.com/web/images/cardsboth2.gif" name="submit" alt="Pay with NOCHEX now" />';
	}
	
	/*-------------------------------------------------------------------------*/
	// Generate Form action [normal]
	/*-------------------------------------------------------------------------*/
	
	function gw_generate_normal_form_action()
	{
		// Test
		//return 'https://www.nochex.com/nochex.dll/apc/testapc';
		
		return "https://www.nochex.com/nochex.dll/checkout";
	}
	
	/*-------------------------------------------------------------------------*/
	// Generate Form action [upgrade]
	/*-------------------------------------------------------------------------*/
	
	function gw_generate_upgrade_form_action()
	{
		return $this->gw_generate_normal_form_action();
	}
	
	/*-------------------------------------------------------------------------*/
	// Generate Form action [normal, recurring]
	/*-------------------------------------------------------------------------*/
	
	function gw_generate_normal_recurring_form_action()
	{
		return $this->gw_generate_normal_form_action();
	}
	
	/*-------------------------------------------------------------------------*/
	// Generate Form action [upgrade, recurring]
	/*-------------------------------------------------------------------------*/
	
	function gw_generate_upgrade_recurring_form_action()
	{
		return $this->gw_generate_normal_form_action();
	}
	
	/*-------------------------------------------------------------------------*/
	// Validate Payment
	// What we need to return:
	// 'currency_code'      => Currency code,
	// 'payment_amount'     => Amount paid,
	// 'payment_status'     => REFUND, ONEOFF, RECURRING
	// 'member_unique_id'   => member's ID,
	// 'purchase_package_id'=> Purchased package ID
	// 'current_package_id' => Current package ID (used for upgrading)
	// 'verified'           => TRUE , FALSE (Gateway verifies info as correct)
	// 'subscription_id'    => (Used for recurring payments)
	// 'transaction_id'     => Gateway transaction ID
	/*-------------------------------------------------------------------------*/
	
	function gw_validate_payment()
	{
		//--------------------------------------
		// INIT
		//--------------------------------------
		
		//--------------------------------------
		// Debug...
		//--------------------------------------
		
		if ( GW_TEST_MODE_ON )
		{
			if ( ! is_array( $_POST ) or ! count( $_POST ) )
			{
				$_POST = $_GET;
			}
		}
		
		//--------------------------------------
		// URLS
		//--------------------------------------
		
		$urls = array( 'curl_full' => 'https://www.nochex.com/nochex.dll/apc/apc',
					   'sock_url'  => 'ssl://www.nochex.com',
					   'sock_path' => '/nochex.dll/apc/apc' );
					   
		//--------------------------------------
		// Throw back to PayPal to verify
		//--------------------------------------
		
		$state = $this->core_post_back( $urls, "", 443 );
		
		//--------------------------------------
		// Check...
		//--------------------------------------
		
		$state = ( strcmp($state, 'AUTHORISED') == 0 ) ? 'INVALID' : 'AUTHORISED';
		
		if ( $state != 'AUTHORISED' AND ! GW_TEST_MODE_ON )
		{
			$this->error = 'not_valid';
			return array( 'verified' => FALSE );
		}
		
		//--------------------------------------
		// Populate return array
		//--------------------------------------
		
		list( $purchase_package_id, $member_id, $cur_sub_id, ) = explode( 'x', trim($_POST['order_id']) );
		
	    $return = array( 'currency_code'      => 'GBP',
						 'payment_amount'     => $_POST['amount'],
						 'member_unique_id'   => intval($member_id),
						 'purchase_package_id'=> intval($purchase_package_id),
						 'current_package_id' => intval($cur_sub_id),
						 'verified'           => TRUE,
						 'verification'		  => $this->ipsclass->input['verification'],
						 'subscription_id'    => '0-'.intval($member_id),
						 'transaction_id'     => $_POST['transaction_id'] );
		
		//--------------------------------------
		// Sort out payment status
		//--------------------------------------
		
		$return['payment_status'] = 'ONEOFF';
		
		//--------------------------------------
		// Pass back to handler
		//--------------------------------------
		
		return $return;
	}
	
	/*-------------------------------------------------------------------------*/
	// Process recurring payment check
	// Return: array( 'amount_paid', 'state' [ PAID, DEAD, FAILED, PENDING ]
	/*-------------------------------------------------------------------------*/
	
	function gw_do_normal_payment_check( $balance_to_pay=0, $total_package_cost=0, $upgrade=0 )
	{
		$this->gateway->error = "";
		
		//--------------------------------------
		// INIT
		//--------------------------------------
		
		$return = array();
		
		//--------------------------------------
		// Completed
		//--------------------------------------
	
		if ( $upgrade AND ( $_POST['amount'] == $balance_to_pay ) )
		{
			//--------------------------------------
			// Paid correct amount
			//--------------------------------------
			
			$return['amount_paid'] = $_POST['amount'];
			$return['state']       = 'PAID';
		}
		else if ( ! $upgrade AND ( $_POST['amount'] == $total_package_cost ) )
		{
			//--------------------------------------
			// Paid correct amount
			//--------------------------------------
			
			$return['amount_paid'] = $_POST['amount'];
			$return['state']       = 'PAID';

		}
		else
		{
			//-----------------------
			// End of subscription
			//-----------------------
			
			$return['state'] = 'FAILED';
		}
		
		return $return;
	}
	
	/*-------------------------------------------------------------------------*/
	// Process recurring payment check
	// Return: array( 'amount_paid', 'state' [ PAID, DEAD, FAILED, PENDING ]
	/*-------------------------------------------------------------------------*/
	
	function gw_do_recurring_payment_check( $balance_to_pay=0, $total_package_cost=0 )
	{
		$this->gateway->error = "";
		
		//--------------------------------------
		// INIT
		//--------------------------------------
		
		$return = array();
		
		return $return;
	}
	
	
	//---------------------------------------
	// Return ACP Package  Variables
	//
	// Returns names for the package custom
	// fields, etc
	//---------------------------------------
	
	function acp_return_package_variables()
	{
	
		$return = array(
						  'subextra_custom_1' => array( 'used' => 0, 'varname' => '' ),
						  'subextra_custom_2' => array( 'used' => 0, 'varname' => '' ),
						  'subextra_custom_3' => array( 'used' => 0, 'varname' => '' ),
						  'subextra_custom_4' => array( 'used' => 0, 'varname' => '' ),
						  'subextra_custom_5' => array( 'used' => 0, 'varname' => '' ),
					   );
					   
		return $return;
	
	}
	
	//---------------------------------------
	// Return ACP Method Variables
	//
	// Returns names for the package custom
	// fields, etc
	//---------------------------------------
	
	function acp_return_method_variables()
	{
	
		$return = array(
						  'submethod_custom_1' => array( 'used' => 0, 'varname' => '' ),
						  'submethod_custom_2' => array( 'used' => 0, 'varname' => '' ),
						  'submethod_custom_3' => array( 'used' => 0, 'varname' => '' ),
						  'submethod_custom_4' => array( 'used' => 0, 'varname' => '' ),
						  'submethod_custom_5' => array( 'used' => 0, 'varname' => '' ),
					   );
					   
		return $return;
	
	}
	
	/*-------------------------------------------------------------------------*/
	// INSTALL ROUTINES
	/*-------------------------------------------------------------------------*/
	
	function install_gateway()
	{
		$this->ipsclass->DB->do_insert( 'subscription_methods', array( 'submethod_title'  => $this->i_am,
																	   'submethod_name'   => $this->i_am,
																	   'submethod_active' => 0 ) );
																   
		$this->install_lang = array( 'gw_test' => 'test test test' );
	}

	
}

 
?>