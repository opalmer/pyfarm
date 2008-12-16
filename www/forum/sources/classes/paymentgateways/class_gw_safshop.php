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
// 'vendor_id'        => The ID of the vendor (not used in all gateways)
// 'product_id'       => The gateway ID of the product (not used in all gateways)
// 'extra_1' thru 5   => Gateway extras ( from DB / tied in method_vars )
//--------------------------------------------------------------------------

class class_gw_module EXTENDS class_gateway
{

	# Global
	var $ipsclass;
	
	# Identify
	var $i_am = 'safshop';
	
	var $can_do_recurring_billing = 0;
	var $can_do_upgrades          = 0;
	
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
		$this->core_add_hidden_field( "productid"    , $items['product_id'] );
		$this->core_add_hidden_field( "vendorid"     , $items['vendor_id'] );
		$this->core_add_hidden_field( "buttonid"     , "Buy" );
		$this->core_add_hidden_field( "item1"        , $items['package_cost'] );
		$this->core_add_hidden_field( "myurl"        , GW_URL_PAYDONE . "&verification=" . md5( $items['member_unique_id'] . $items['package_id'] . $this->ipsclass->vars['sql_pass'] ) );
		$this->core_add_hidden_field( "act"          , "paysubs" );
		$this->core_add_hidden_field( "CODE"         , "incoming" );
		$this->core_add_hidden_field( "type"         , "safshop" );
		
		//$this->core_add_hidden_field( "verification" , md5( $items['member_unique_id'] . $items['package_id'] . $this->ipsclass->vars['sql_pass'] ) );
		
		return $this->core_compile_hidden_fields();
	}
	
	/*-------------------------------------------------------------------------*/
	// Generate hidden fields [ upgrade screen ]
	/*-------------------------------------------------------------------------*/
	
	function gw_generate_hidden_fields_upgrade( $items=array() )
	{
		// Not available for this gateway
		
		return $this->core_compile_hidden_fields();
	}
	
	/*-------------------------------------------------------------------------*/
	// Generate Purchase button
	/*-------------------------------------------------------------------------*/
	
	function gw_generate_purchase_button()
	{
		return '<input type="submit" name="b1" value="'.$this->ipsclass->lang['paywith_gen'].'" />';
	}
	
	/*-------------------------------------------------------------------------*/
	// Generate Form action [normal]
	/*-------------------------------------------------------------------------*/
	
	function gw_generate_normal_form_action()
	{
		return "https://order.safshop.net/servicepayment.cgi";
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
		// Populate return array
		//--------------------------------------
		
		list( $purchase_package_id, $member_id, $cur_sub_id, ) = explode( 'x', trim($_POST['ordernumber']) );
		
	    $return = array( 'currency_code'      => 'USD',
						 'payment_amount'     => 0,
						 'member_unique_id'   => intval($member_id),
						 'purchase_package_id'=> intval($purchase_package_id),
						 'current_package_id' => intval($cur_sub_id),
						 'verified'           => $_POST['safshopresult'] ? TRUE : FALSE,
						 'verification'		  => $this->ipsclass->input['verification'],
						 'subscription_id'    => '0-'.intval($member_id),
						 'transaction_id'     => $_POST['safshopresult'] );
		
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
	
		$return['amount_paid'] = $total_package_cost;
		$return['state']       = 'PAID';
		
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
	// INSTALL Gateway...
	/*-------------------------------------------------------------------------*/
	
	function install_gateway()
	{
		//--------------------------------------
		// DB queries
		//--------------------------------------
		
		$this->db_info = array( 'human_title'         => 'Safshop',
								'human_desc'		  => 'Accepts all major credit cards',
								'module_name'         => $this->i_am,
								'allow_creditcards'   => 1,
								'allow_auto_validate' => 1,
								'default_currency'    => 'USD' );
							   
		
		$this->install_lang = array( 'gw_'.$this->i_am => "Click the button below to complete this order via Safshop's website. All costs will be converted into USD for Safshop's website" );
	}	
	
}

 
?>