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
	var $i_am = '2checkout';
	
	var $can_do_recurring_billing = 0;
	var $can_do_upgrades          = 0;
	
	# No post back
	var $no_postback = 1;
	
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
		$this->core_add_hidden_field( "merchant_order_id"    , $items['package_id'].'x'.$items['member_unique_id'].'x0' );
		$this->core_add_hidden_field( "sid"                  , $items['vendor_id'] );
		$this->core_add_hidden_field( "product_id"           , $items['product_id'] );
		$this->core_add_hidden_field( "quantity"             , 1 );
		
		$this->core_add_hidden_field( "verification" 		 , md5( $items['member_unique_id'] . $items['package_id'] . $this->ipsclass->vars['sql_pass'] ) );
		
		return $this->core_compile_hidden_fields();
	}
	
	/*-------------------------------------------------------------------------*/
	// Generate hidden fields [ upgrade screen ]
	/*-------------------------------------------------------------------------*/
	
	function gw_generate_hidden_fields_upgrade( $items=array() )
	{
		// NO method
		
		return $this->core_compile_hidden_fields();
	}
	
	/*-------------------------------------------------------------------------*/
	// Generate Purchase button
	/*-------------------------------------------------------------------------*/
	
	function gw_generate_purchase_button()
	{
		return '<input type="submit" name="b1" value="'.$this->ipsclass->lang['s_continue_button2'].'" />';
	}
	
	/*-------------------------------------------------------------------------*/
	// Generate Form action [normal]
	/*-------------------------------------------------------------------------*/
	
	function gw_generate_normal_form_action()
	{
		return "https://www.2checkout.com/cgi-bin/sbuyers/purchase.2c";
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
		
		$referers  = array( 'www.2checkout.com', '2checkout.com', $this->ipsclass->my_getenv('HTTP_HOST'), str_replace( 'www.', '', $this->ipsclass->my_getenv('HTTP_HOST') ) );
		$got_match = 0;
		
		//--------------------------------------
		// Debug...
		//--------------------------------------
		
		//if ( GW_TEST_MODE_ON )
		//{
			if ( ! is_array( $_POST ) or ! count( $_POST ) )
			{
				$_POST = $_GET;
			}
		//}
		
		//--------------------------------------
		// Check...
		//--------------------------------------
		
		if( $this->ipsclass->my_getenv('HTTP_REFERER') )
		{
			foreach( $referers as $r )
			{
				if ( preg_match( "#http(s)?://$r#i", $this->ipsclass->my_getenv('HTTP_REFERER') ) )
				{
					$got_match = 1;
				}
			}
			
			if ( ! $got_match AND ! GW_TEST_MODE_ON )
			{
				$this->error = 'not_valid';
				return array( 'verified' => FALSE );
			}
		}
		
		//--------------------------------------
		// Populate return array
		//--------------------------------------
		
		list( $purchase_package_id, $member_id, $cur_sub_id, ) = explode( 'x', trim($this->ipsclass->input['merchant_order_id']) );
		
	    $return = array( 'currency_code'      => 'USD',
						 'payment_amount'     => $this->ipsclass->input['total'],
						 'member_unique_id'   => intval($member_id),
						 'purchase_package_id'=> intval($purchase_package_id),
						 'current_package_id' => intval($cur_sub_id),
						 'verified'           => TRUE,
						 'verification'		  => $this->ipsclass->input['verification'],
						 'subscription_id'    => '0-'.intval($member_id),
						 'transaction_id'     => $this->ipsclass->input['order_number'] .'x'.time() );
		
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
		
		$_POST['amount'] = $_POST['amount'] ? $_POST['amount'] : $_POST['total'];
		
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
	// INSTALL Gateway...
	/*-------------------------------------------------------------------------*/
	
	function install_gateway()
	{
		//--------------------------------------
		// DB queries
		//--------------------------------------
		
		$this->db_info = array( 'human_title'         => '2CheckOut',
								'human_desc'		  => "All major credit cards accepted. See <a href='http://www.2checkout.com/cgi-bin/aff.2c?affid=28376' target='_blank'>2CheckOut</a> for more information.",
								'module_name'         => $this->i_am,
								'allow_creditcards'   => 1,
								'allow_auto_validate' => 1,
								'default_currency'    => 'USD' );
							   
		
		$this->install_lang = array( 'gw_'.$this->i_am => 'Click the button below to complete this order via our secure online payment page.' );
	}
	
	
}

 
?>