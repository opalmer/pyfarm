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
	var $i_am = 'authorizenet';
	
	var $can_do_recurring_billing = 0;
	var $can_do_upgrades          = 1;
	
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
		$txn_key = $items['company_email'];								 
		$fp_seq  = rand(0,1000);
		$fp_time = time();
		$fp_hash = $this->my_calculatefp( $items['vendor_id'], $txn_key, $items['package_cost'], $fp_seq, $fp_time, $items['currency_code'] );
		
		$this->core_add_hidden_field( "x_cust_id"        , $items['package_id'].'x'.$items['member_unique_id'].'x0' );
		$this->core_add_hidden_field( "x_login"          , $items['vendor_id'] );
		$this->core_add_hidden_field( "x_amount"         , $items['package_cost'] );
		$this->core_add_hidden_field( "x_currency_code"  , $items['currency_code'] );
		$this->core_add_hidden_field( "x_description"    , $items['package_title'] );
		#$this->core_add_hidden_field( "x_relay_response" , 'TRUE'  );
		
		$this->core_add_hidden_field( "x_receipt_link_method" , 'POST'  );
		$this->core_add_hidden_field( "x_receipt_link_text" , 'Return to our site!'  );
		$this->core_add_hidden_field( "x_receipt_link_url"      , GW_URL_VALIDATE . "&verification=" . md5( $items['member_unique_id'] . $items['package_id'] . $this->ipsclass->vars['sql_pass'] ) );
		
		$this->core_add_hidden_field( "x_show_form"      , 'PAYMENT_FORM'  );
		$this->core_add_hidden_field( "x_test_request"   , 'FALSE'  );
		$this->core_add_hidden_field( "x_fp_hash"        , $fp_hash  );
		$this->core_add_hidden_field( "x_fp_timestamp"   , $fp_time  );
		$this->core_add_hidden_field( "x_fp_sequence"    , $fp_seq  );
		#$this->core_add_hidden_field( "x_relay_url"      , GW_URL_VALIDATE   );
		$this->core_add_hidden_field( "x_invoice_num"    , $fp_time.'-'.$fp_seq.'-'.$items['currency_code'] );
		
		//$this->core_add_hidden_field( "verification" 	 , md5( $items['member_unique_id'] . $items['package_id'] . $this->ipsclass->vars['sql_pass'] ) );
		
		return $this->core_compile_hidden_fields();
	}
	
	/*-------------------------------------------------------------------------*/
	// Generate hidden fields [ upgrade screen ]
	/*-------------------------------------------------------------------------*/
	
	function gw_generate_hidden_fields_upgrade( $items=array() )
	{
		$txn_key = $items['company_email'];								 
		$fp_seq  = rand(0,1000);
		$fp_time = time();
		$fp_hash = $this->my_calculatefp( $items['vendor_id'], $txn_key, $items['ttr_balance'], $fp_seq, $fp_time, $items['currency_code'] );
		
		$this->core_add_hidden_field( "x_cust_id"        , $items['package_id'].'x'.$items['member_unique_id'].'x'.$items['ttr_package_id'] );
		$this->core_add_hidden_field( "x_login"          , $items['vendor_id'] );
		$this->core_add_hidden_field( "x_amount"         , $items['ttr_balance'] );
		$this->core_add_hidden_field( "x_currency_code"  , $items['currency_code'] );
		$this->core_add_hidden_field( "x_description"    , $items['package_title'] );
		
		$this->core_add_hidden_field( "x_receipt_link_method" , 'POST'  );
		$this->core_add_hidden_field( "x_receipt_link_text" , 'Return to our site!'  );
		$this->core_add_hidden_field( "x_receipt_link_url"      , GW_URL_VALIDATE . "&verification=" . md5( $items['member_unique_id'] . $items['package_id'] . $this->ipsclass->vars['sql_pass'] ) );
		
		#$this->core_add_hidden_field( "x_relay_response" , 'TRUE'  );
		$this->core_add_hidden_field( "x_show_form"      , 'PAYMENT_FORM'  );
		$this->core_add_hidden_field( "x_test_request"   , 'FALSE'  );
		$this->core_add_hidden_field( "x_fp_hash"        , $fp_hash  );
		$this->core_add_hidden_field( "x_fp_timestamp"   , $fp_time  );
		$this->core_add_hidden_field( "x_fp_sequence"    , $fp_seq  );
		#$this->core_add_hidden_field( "x_relay_url"      , GW_URL_VALIDATE   );
		$this->core_add_hidden_field( "x_invoice_num"    , $fp_time.'-'.$fp_seq.'-'.$items['currency_code'] );
		
		//$this->core_add_hidden_field( "verification" 	 , md5( $items['member_unique_id'] . $items['package_id'] . $this->ipsclass->vars['sql_pass'] ) );
		
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
		return "https://secure.authorize.net/gateway/transact.dll";
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
	
	function gw_validate_payment( $extra=array() )
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
		// Check hash
		//--------------------------------------
		
		list( $fp_time, $fp_seq, $currency_code, )             = explode( '-', $this->ipsclass->input['x_invoice_num'] );
		list( $purchase_package_id, $member_id, $cur_sub_id, ) = explode( 'x', trim($this->ipsclass->input['x_cust_id']) );
		
		$in_hash          = $this->ipsclass->input['x_md5_hash'] ? $this->ipsclass->input['x_md5_hash'] : $this->ipsclass->input['x_MD5_Hash'];
		$txn_key          = $extra['company_email'];								 
		$fp_hash          = $this->my_calculatefp( $extra['vendor_id'], $txn_key, $this->ipsclass->input['x_amount'], intval($fp_seq), intval($fp_time), trim($currency_code) );
		$an_response_code = $this->ipsclass->input['x_response_code'];
		$test_hash        = strtoupper( md5( $extra['vendor_id'] . $this->ipsclass->input['x_trans_id'] . $this->ipsclass->input['x_amount']) );
		
		if ( $in_hash != $test_hash )
		{
			$an_response_code = 0;
		}
		
		//--------------------------------------
		// Check...
		//--------------------------------------
		
		if ( $an_response_code != 1 AND ! GW_TEST_MODE_ON )
		{
			$this->error = 'not_valid';
			return array( 'verified' => FALSE );
		}

		//--------------------------------------
		// Populate return array
		//--------------------------------------
		
	    $return = array( 'currency_code'      => trim($currency_code),
						 'payment_amount'     => $this->ipsclass->input['x_amount'],
						 'member_unique_id'   => intval($member_id),
						 'purchase_package_id'=> intval($purchase_package_id),
						 'current_package_id' => intval($cur_sub_id),
						 'verified'           => TRUE,
						 'verification'		  => $this->ipsclass->input['verification'],
						 'subscription_id'    => '0-'.intval($member_id),
						 'transaction_id'     => $this->ipsclass->input['x_trans_id'] );
		
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
	
		if ( $upgrade )
		{
			//--------------------------------------
			// Completed
			//--------------------------------------
		
			$return['amount_paid'] = $balance_to_pay;
			$return['state']       = 'PAID';
		}
		else
		{
			//--------------------------------------
			// Completed
			//--------------------------------------
		
			$return['amount_paid'] = $total_package_cost;
			$return['state']       = 'PAID';
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
	
	/*-------------------------------------------------------------------------*/
	// INSTALL Gateway...
	/*-------------------------------------------------------------------------*/
	
	function install_gateway()
	{
		//--------------------------------------
		// DB queries
		//--------------------------------------
		
		$this->db_info = array( 'human_title'         => 'Authorize.net',
								'human_desc'		  => 'All major credit cards accepted',
								'module_name'         => $this->i_am,
								'allow_creditcards'   => 1,
								'allow_auto_validate' => 1,
								'default_currency'    => 'USD' );
							   
		
		$this->install_lang = array( 'gw_'.$this->i_am => 'Click the button below to complete this order via our secure online payment page.' );
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
	
	function my_hmac ($key, $data)
	{
	   // RFC 2104 HMAC implementation for php.
	   // Creates an md5 HMAC.
	   // Eliminates the need to install mhash to compute a HMAC
	   // Hacked by Lance Rushing
	
	   $b = 64; // byte length for md5
	   if (strlen($key) > $b) {
		   $key = pack("H*",md5($key));
	   }
	   $key  = str_pad($key, $b, chr(0x00));
	   $ipad = str_pad('', $b, chr(0x36));
	   $opad = str_pad('', $b, chr(0x5c));
	   $k_ipad = $key ^ $ipad ;
	   $k_opad = $key ^ $opad;
	
	   return md5($k_opad  . pack("H*",md5($k_ipad . $data)));
	}
	
	function my_calculatefp($loginid, $txnkey, $amount, $sequence, $tstamp, $currency = "")
	{
  		return $this->my_hmac($txnkey, $loginid . "^" . $sequence . "^" . $tstamp . "^" . $amount . "^" . $currency);
	}

	
	
}

 
?>