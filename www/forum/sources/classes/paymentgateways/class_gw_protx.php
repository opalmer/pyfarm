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
	var $i_am = 'protx';
	
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
		$username = $items['extra_1'];
		$password = $items['extra_2'];
	
		//---------------------------------------
		// Generate crypt string
		//---------------------------------------
		
		$plain  = "VendorTxCode="  . (rand(0,32000)*rand(0,32000))."x{$items['member_unique_id']}x{$items['package_id']}x0" . "&";
		$plain .= "Amount="        . $items['package_cost'] . "&";
		$plain .= "Currency="      . $items['currency_code'] . "&";
		$plain .= "Description="   . $items['package_title'] ."&";
		$plain .= "SuccessURL="    . GW_URL_VALIDATE . "&verification=" . md5( $items['member_unique_id'] . $items['package_id'] . $this->ipsclass->vars['sql_pass'] ) . "&";
		$plain .= "FailureURL="    . GW_URL_VALIDATE . "&verification=" . md5( $items['member_unique_id'] . $items['package_id'] . $this->ipsclass->vars['sql_pass'] ) . "&";
		$plain .= "CustomerName=&";
		$plain .= "CustomerEmail=" . $items['member_email'] . "&";
		$plain .= "VendorEMail="   . $items['company_email'] . "&";
		$plain .= "DeliveryAddress=&";
      	$plain .= "DeliveryPostCode=&";
      	$plain .= "BillingAddress=&";
      	$plain .= "BillingPostCode=&";
		$plain .= "ContactNumber=&";
		$plain .= "ContactFax=&";
		$plain .= "AllowGiftAid=&";
		$plain .= "ApplyAVSCV2=&";
		$plain .= "Apply3DSecure=";
      
		$crypt = base64_encode( $this->_simple_xor( $plain, $password ) );
		
		$this->core_add_hidden_field( "Crypt"       , $crypt );
		$this->core_add_hidden_field( "Vendor"      , $username );
		$this->core_add_hidden_field( "TxType"      , 'PAYMENT' );
		$this->core_add_hidden_field( "VPSProtocol" , '2.22' );

		return $this->core_compile_hidden_fields();
	}
	
	/*-------------------------------------------------------------------------*/
	// Generate hidden fields [ upgrade screen ]
	/*-------------------------------------------------------------------------*/
	
	function gw_generate_hidden_fields_upgrade( $items=array() )
	{
		$username = $items['extra_1'];
		$password = $items['extra_2'];
		
		//---------------------------------------
		// Generate crypt string
		//---------------------------------------
		
		$plain  = "VendorTxCode="  . (rand(0,32000)*rand(0,32000))."x{$items['member_unique_id']}x{$items['package_id']}x{$items['ttr_package_id']}" . "&";
		$plain .= "Amount="        . $items['ttr_balance'] . "&";
		$plain .= "Currency="      . $items['currency_code'] . "&";
		$plain .= "Description="   . $items['package_title'] ."&";
		$plain .= "SuccessURL="    . GW_URL_VALIDATE . "&verification=" . md5( $items['member_unique_id'] . $items['package_id'] . $this->ipsclass->vars['sql_pass'] ) . "&";
		$plain .= "FailureURL="    . GW_URL_VALIDATE . "&verification=" . md5( $items['member_unique_id'] . $items['package_id'] . $this->ipsclass->vars['sql_pass'] ) . "&";
		$plain .= "CustomerName=&";
		$plain .= "CustomerEmail=" . $items['member_email'] . "&";
		$plain .= "VendorEMail="   . $items['company_email'] . "&";
		$plain .= "DeliveryAddress=&";
      	$plain .= "DeliveryPostCode=&";
      	$plain .= "BillingAddress=&";
      	$plain .= "BillingPostCode=&";
		$plain .= "ContactNumber=&";
		$plain .= "ContactFax=&";
		$plain .= "AllowGiftAid=&";
		$plain .= "ApplyAVSCV2=&";
		$plain .= "Apply3DSecure=";
      	
		$crypt = base64_encode( $this->_simple_xor( $plain, $password ) );
		
		$this->core_add_hidden_field( "Crypt"       , $crypt );
		$this->core_add_hidden_field( "Vendor"      , $username );
		$this->core_add_hidden_field( "TxType"      , 'PAYMENT' );
		$this->core_add_hidden_field( "VPSProtocol" , '2.22' );
		
		return $this->core_compile_hidden_fields();
	}
	
	/*-------------------------------------------------------------------------*/
	// Generate Purchase button
	/*-------------------------------------------------------------------------*/
	
	function gw_generate_purchase_button()
	{
		return '<input type="submit" value="'.$this->ipsclass->lang['paywith_protx'].'" />';
	}
	
	/*-------------------------------------------------------------------------*/
	// Generate Form action [normal]
	/*-------------------------------------------------------------------------*/
	
	function gw_generate_normal_form_action()
	{
		# test
		//return 'https://ukvpstest.protx.com/vps2form/submit.asp';
		
		return "https://ukvps.protx.com/vps2form/submit.asp";
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
		
		$username = $extra['extra_1'];
		$password = $extra['extra_2'];
		
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
		// Process GET data
		//--------------------------------------
		
		$_GET['crypt'] = str_replace(" ", "+", $_GET['crypt']);
		
      	$values = $this->_get_token( $this->_simple_xor( base64_decode($_GET['crypt']), $password ) );
		
		foreach( $values as $k => $v )
		{
			$_POST[ 'px_'.$k ] = $v;
		}
		
		//--------------------------------------
		// Check...
		//--------------------------------------
		
		if ( $values['Status'] != 'OK' AND ! GW_TEST_MODE_ON )
		{
			$this->error = 'not_valid';
			return array( 'verified' => FALSE );
		}
		
		//--------------------------------------
		// Populate return array
		//--------------------------------------
		
		list( $tix, $member_id, $purchase_package_id, $cur_sub_id  ) = explode( "x", $values['VendorTxCode'] );
		
	    $return = array( 'currency_code'      => 'GBP',
						 'payment_amount'     => sprintf( "%.2f", $values['Amount'] ),
						 'member_unique_id'   => intval($member_id),
						 'purchase_package_id'=> intval($purchase_package_id),
						 'current_package_id' => intval($cur_sub_id),
						 'verified'           => TRUE,
						 'verification'		  => $this->ipsclass->input['verification'],
						 'subscription_id'    => '0-'.intval($member_id),
						 'transaction_id'     => $values['VPSTxId'] );
		
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
						  'submethod_custom_1' => array( 'used' => 1, 'formname' => 'Protx User Name', 'formextra' => 'This is the username assigned to your Protx account' ),
						  'submethod_custom_2' => array( 'used' => 1, 'formname' => 'Protx Encryption Password' , 'formextra' => 'This is the password assigned to your Protx account  to encrypt the form data' ),
						  'submethod_custom_3' => array( 'used' => 0, 'varname' => '' ),
						  'submethod_custom_4' => array( 'used' => 0, 'varname' => '' ),
						  'submethod_custom_5' => array( 'used' => 0, 'varname' => '' ),
					   );
					   
		return $return;
	
	}
	
	function _simple_xor($instr, $k)
	{
     	$kList = array();
    	$output = "";

    	for($i = 0; $i < strlen($k); $i++)
    	{
     		$kList[$i] = ord(substr($k, $i, 1));
      	}

      	for($i = 0; $i < strlen($instr); $i++)
      	{
        	$output.= chr(ord(substr($instr, $i, 1)) ^ ($kList[$i % strlen($k)]));
      	}

      	return $output;
    }

	function _get_token($thisString)
	{
		$Tokens = array(
		    "Status",
		    "StatusDetail",
		    "VendorTxCode",
		    "VPSTxId",
		    "TxAuthNo",
		    "Amount",
		    "AVSCV2", 
		    "AddressResult", 
		    "PostCodeResult", 
		    "CV2Result", 
		    "GiftAid", 
		    "3DSecureStatus", 
		    "CAVV" );

		$output = array();
		$resultArray = array();

		for ($i = count($Tokens)-1; $i >= 0 ; $i--)
		{
			$start = strpos($thisString, $Tokens[$i]);
			
        	if ($start !== false)
        	{
          		$resultArray[$i]->start = $start;
          		$resultArray[$i]->token = $Tokens[$i];
       		}
      	}

      	sort($resultArray);

      	for ($i = 0; $i<count($resultArray); $i++)
      	{
        	$valueStart = $resultArray[$i]->start + strlen($resultArray[$i]->token) + 1;
        	if ($i==(count($resultArray)-1))
        	{
          		$output[$resultArray[$i]->token] = substr($thisString, $valueStart);
        	}
        	else
        	{
          		$valueLength = $resultArray[$i+1]->start - $resultArray[$i]->start - strlen($resultArray[$i]->token) - 2;
          		$output[$resultArray[$i]->token] = substr($thisString, $valueStart, $valueLength);
        	}
      	}

      	return $output;
    }
    
	/*-------------------------------------------------------------------------*/
	// INSTALL Gateway...
	/*-------------------------------------------------------------------------*/
	
	function install_gateway()
	{
		//--------------------------------------
		// DB queries
		//--------------------------------------
		
		$this->db_info = array( 'human_title'         => 'Protx',
								'human_desc'		  => 'Accepts all major credit cards',
								'module_name'         => $this->i_am,
								'allow_creditcards'   => 1,
								'allow_auto_validate' => 1,
								'default_currency'    => 'GBP' );
							   
		
		$this->install_lang = array( 'gw_'.$this->i_am => "Click the button below to complete this order via Protx's website" );
	}	
	
}

 
?>