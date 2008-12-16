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
	var $i_am = 'PayPal';
	
	var $can_do_recurring_billing = 1;
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
		$this->core_add_hidden_field( "cmd"          , "_xclick-subscriptions" );
		$this->core_add_hidden_field( "currency_code", $items['currency_code'] );
		$this->core_add_hidden_field( "custom"       , $items['member_unique_id'] );
		$this->core_add_hidden_field( "item_number"  , $items['package_id'] );
		$this->core_add_hidden_field( "t3"           , strtoupper($items['duration_unit'] ));
		$this->core_add_hidden_field( "p3"           , $items['duration_int'] );
		$this->core_add_hidden_field( "a3"           , $items['package_cost'] );
		$this->core_add_hidden_field( "business"     , $items['company_email'] );
		$this->core_add_hidden_field( "item_name"    , $items['package_title'] );
		$this->core_add_hidden_field( "no_shipping"  , 1 );
		$this->core_add_hidden_field( "src"          , 1 );
		$this->core_add_hidden_field( "rm"           , 2 );
		$this->core_add_hidden_field( "no_note"      , 1 );
		$this->core_add_hidden_field( "notify_url"   , GW_URL_VALIDATE . "&verification=" . md5( $items['member_unique_id'] . $items['package_id'] . $this->ipsclass->vars['sql_pass'] ) );
		$this->core_add_hidden_field( "return"       , GW_URL_PAYDONE . "&verification=" . md5( $items['member_unique_id'] . $items['package_id'] . $this->ipsclass->vars['sql_pass'] ) );
		$this->core_add_hidden_field( "cancel_return", GW_URL_PAYCANCEL . "&verification=" . md5( $items['member_unique_id'] . $items['package_id'] . $this->ipsclass->vars['sql_pass'] ) );
		
		//$this->core_add_hidden_field( "verification" , md5( $items['member_unique_id'] . $items['package_id'] . $this->ipsclass->vars['sql_pass'] ) );
		
		return $this->core_compile_hidden_fields();
	}
	
	/*-------------------------------------------------------------------------*/
	// Generate hidden fields [ Recurring, upgrade screen ]
	/*-------------------------------------------------------------------------*/
	
	function gw_generate_hidden_fields_upgrade_recurring( $items=array() )
	{
		$this->core_add_hidden_field( "cmd"          , "_xclick-subscriptions" );
		$this->core_add_hidden_field( "currency_code", $items['currency_code'] );
		$this->core_add_hidden_field( "custom"       , $items['member_unique_id'] );
		$this->core_add_hidden_field( "item_number"  , $items['package_id'] );
		$this->core_add_hidden_field( "t3"           , strtoupper($items['duration_unit']) );
		$this->core_add_hidden_field( "p3"           , $items['duration_int'] );
		$this->core_add_hidden_field( "a3"           , $items['package_cost'] );
		$this->core_add_hidden_field( "a1"           , $items['ttr_balance'] );
		$this->core_add_hidden_field( "p1"           , $items['ttr_int'] );
		$this->core_add_hidden_field( "t1"           , strtoupper($items['ttr_unit'] ));
		$this->core_add_hidden_field( "memo"         , "upgrade" );
		$this->core_add_hidden_field( "invoice"      , $items['ttr_package_id'].'x'.$items['package_id'].'x'.$items['member_unique_id'] );
		$this->core_add_hidden_field( "business"     , $items['company_email'] );
		$this->core_add_hidden_field( "item_name"    , $items['package_title'] );
		$this->core_add_hidden_field( "no_shipping"  , 1 );
		$this->core_add_hidden_field( "src"          , 1 );
		$this->core_add_hidden_field( "no_note"      , 1 );
		$this->core_add_hidden_field( "rm"           , 2 );
		$this->core_add_hidden_field( "notify_url"   , GW_URL_VALIDATE . "&verification=" . md5( $items['member_unique_id'] . $items['package_id'] . $this->ipsclass->vars['sql_pass'] ) );
		$this->core_add_hidden_field( "return"       , GW_URL_PAYDONE . "&verification=" . md5( $items['member_unique_id'] . $items['package_id'] . $this->ipsclass->vars['sql_pass'] ) );
		$this->core_add_hidden_field( "cancel_return", GW_URL_PAYCANCEL . "&verification=" . md5( $items['member_unique_id'] . $items['package_id'] . $this->ipsclass->vars['sql_pass'] ) );
		
		//$this->core_add_hidden_field( "verification" , md5( $items['member_unique_id'] . $items['package_id'] . $this->ipsclass->vars['sql_pass'] ) );
		
		return $this->core_compile_hidden_fields();
	}
	
	/*-------------------------------------------------------------------------*/
	// Generate hidden fields [ normal screen ]
	/*-------------------------------------------------------------------------*/
	
	function gw_generate_hidden_fields_normal( $items=array() )
	{
		$this->core_add_hidden_field( "cmd"          , "_xclick" );
		$this->core_add_hidden_field( "currency_code", $items['currency_code'] );
		$this->core_add_hidden_field( "custom"       , $items['member_unique_id'] );
		$this->core_add_hidden_field( "item_number"  , $items['package_id'] );
		$this->core_add_hidden_field( "item_name"    , $items['package_title'] );
		$this->core_add_hidden_field( "amount"       , $items['package_cost'] );
		$this->core_add_hidden_field( "business"     , $items['company_email'] );
		$this->core_add_hidden_field( "no_shipping"  , 1 );
		$this->core_add_hidden_field( "src"          , 1 );
		$this->core_add_hidden_field( "notify_url"   , GW_URL_VALIDATE . "&verification=" . md5( $items['member_unique_id'] . $items['package_id'] . $this->ipsclass->vars['sql_pass'] ) );
		$this->core_add_hidden_field( "return"       , GW_URL_PAYDONE . "&verification=" . md5( $items['member_unique_id'] . $items['package_id'] . $this->ipsclass->vars['sql_pass'] ) );
		$this->core_add_hidden_field( "cancel_return", GW_URL_PAYCANCEL . "&verification=" . md5( $items['member_unique_id'] . $items['package_id'] . $this->ipsclass->vars['sql_pass'] ) );
		
		//$this->core_add_hidden_field( "verification" , md5( $items['member_unique_id'] . $items['package_id'] . $this->ipsclass->vars['sql_pass'] ) );
		
		return $this->core_compile_hidden_fields();
	}
	
	/*-------------------------------------------------------------------------*/
	// Generate hidden fields [ upgrade screen ]
	/*-------------------------------------------------------------------------*/
	
	function gw_generate_hidden_fields_upgrade( $items=array() )
	{
		$this->core_add_hidden_field( "cmd"          , "_xclick" );
		$this->core_add_hidden_field( "currency_code", $items['currency_code'] );
		$this->core_add_hidden_field( "custom"       , $items['member_unique_id'] );
		$this->core_add_hidden_field( "item_number"  , $items['package_id'] );
		$this->core_add_hidden_field( "business"     , $items['company_email'] );
		$this->core_add_hidden_field( "item_name"    , $items['package_title'] );
		$this->core_add_hidden_field( "invoice"      , $items['ttr_package_id'].'x'.$items['package_id'].'x'.$items['member_unique_id'] );
		$this->core_add_hidden_field( "amount"       , $items['ttr_balance'] );
		$this->core_add_hidden_field( "no_shipping"  , 1 );
		$this->core_add_hidden_field( "src"          , 1 );
		$this->core_add_hidden_field( "notify_url"   , GW_URL_VALIDATE . "&verification=" . md5( $items['member_unique_id'] . $items['package_id'] . $this->ipsclass->vars['sql_pass'] ) );
		$this->core_add_hidden_field( "return"       , GW_URL_PAYDONE . "&verification=" . md5( $items['member_unique_id'] . $items['package_id'] . $this->ipsclass->vars['sql_pass'] ) );
		$this->core_add_hidden_field( "cancel_return", GW_URL_PAYCANCEL . "&verification=" . md5( $items['member_unique_id'] . $items['package_id'] . $this->ipsclass->vars['sql_pass'] ) );
		
		//$this->core_add_hidden_field( "verification" , md5( $items['member_unique_id'] . $items['package_id'] . $this->ipsclass->vars['sql_pass'] ) );
		
		return $this->core_compile_hidden_fields();
	}
	
	/*-------------------------------------------------------------------------*/
	// Generate Purchase button
	/*-------------------------------------------------------------------------*/
	
	function gw_generate_purchase_button()
	{
		return '<input type="image" src="https://www.paypal.com/images/x-click-but6.gif" name="submit" alt="'.$this->ipsclass->lang['paywith_paypal'].'" />';
	}
	
	/*-------------------------------------------------------------------------*/
	// Generate Form action [normal]
	/*-------------------------------------------------------------------------*/
	
	function gw_generate_normal_form_action()
	{
		return "https://www.paypal.com/cgi-bin/webscr";
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
		
		$post_back[] = 'cmd=_notify-validate';
		
		foreach ($_POST as $key => $val)
		{
			$post_back[] = $key . '=' . urlencode (stripslashes($val));
		}
		
		$post_back_str = implode('&', $post_back);
		
		//--------------------------------------
		// URLS
		//--------------------------------------
		
		$urls = array( 'curl_full' => 'http://www.paypal.com/cgi-bin/webscr',
					   'sock_url'  => 'www.paypal.com',
					   'sock_path' => '/cgi-bin/webscr' );
					   
		//--------------------------------------
		// Throw back to PayPal to verify
		//--------------------------------------
		
		$state = $this->core_post_back( $urls, $post_back_str, 80 );
		
		//--------------------------------------
		// Check...
		//--------------------------------------
		
		$state = ( stristr($state, 'VERIFIED') ) ? 'VERIFIED' : 'INVALID';
		
		if ( $state != 'VERIFIED' )
		{
			if ( ! GW_TEST_MODE_ON )
			{
				$this->error = 'not_valid';
				return array( 'verified' => FALSE );
			}
		}
		
		//--------------------------------------
		// Fix ticket: #56264 Second POST - we can ignore
		//--------------------------------------
		
		if ( ! $_POST['txn_id'] and $_POST['txn_type'] == 'subscr_signup' )
		{
			exit();
		}
		
		//--------------------------------------
		// Populate return array
		//--------------------------------------
		
		list( $cur_sub_id, ) = explode( 'x', trim($_POST['invoice']) );
		
	    $return = array( 'currency_code'      => $_POST['mc_currency'],
						 'payment_amount'     => $_POST['mc_gross'],
						 'member_unique_id'   => intval($_POST['custom']),
						 'purchase_package_id'=> intval($_POST['item_number']),
						 'current_package_id' => intval($cur_sub_id),
						 'verified'           => TRUE,
						 'verification'		  => $this->ipsclass->input['verification'],
						 'subscription_id'    => $_POST['subscr_id'],
						 'transaction_id'     => $_POST['txn_id'] );
		
		//--------------------------------------
		// Sort out payment status
		//--------------------------------------
		
		if ( $_POST['payment_status'] == 'Refunded' )
		{
			$return['payment_status'] = 'REFUND';
		}
		else if( $_POST['txn_type'] == 'subscr_cancel' )
		{
			$return['payment_status'] = 'CANCEL';
		}
		else if ( strstr( $_POST['txn_type'], 'subscr_' ) )
		{
			$return['payment_status'] = 'RECURRING';
		}
		else if ( $_POST['txn_type'] == 'web_accept' )
		{
			$return['payment_status'] = 'ONEOFF';
		}
		else
		{
			$return['payment_status'] = '';
		}
		
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
	
		if ( $_POST['payment_status'] == 'Completed' )
		{
			//--------------------------------------
			// Check amount..
			//--------------------------------------
			
			if ( $_POST['mc_gross'] == $total_package_cost )
			{
				//--------------------------------------
				// Paid correct amount
				//--------------------------------------
				
				$return['amount_paid'] = $_POST['mc_gross'];
				$return['state']       = 'PAID';
			}
			else
			{
				if ( $upgrade )
				{	
					//--------------------------------------
					// Upgrading....
					//--------------------------------------
					
					if ( $_POST['mc_gross'] == $balance_to_pay )
					{
						$return['amount_paid'] = $_POST['mc_gross'];
						$return['state']       = 'PAID';
					}
					
				}
				else
				{
					//--------------------------------------
					// Incorrect amount
					//--------------------------------------
					
					$this->error = "Wrong payment amount. Looking for {$total_package_cost}, got {$_POST['mc_gross']}";
					return;
				}
			}
			
		}
		else if ( $_POST['payment_status'] == 'Pending' )
		{
			//-----------------------
			// Failed...
			//-----------------------
			
			$return['state'] = 'PENDING';
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
		
		//--------------------------------------
		// Sign up
		//--------------------------------------
		
		if ( $_POST['txn_type'] == 'subscr_signup' OR $_POST['txn_type'] == 'subscr_payment' )
		{
			//--------------------------------------
			// Check amount..
			//--------------------------------------
			
			if ( $_POST['amount1'] )
			{
				//--------------------------------------
				// First period, get amount.
				//--------------------------------------
				
				if ( $balance_to_pay != $_POST['amount1'] )
				{
					$this->error = "Wrong upgrade subs amount. Looking for $balance_to_pay, got {$_POST['amount1']}";
					return;
				}
				
				$return['amount_paid']  = $_POST['amount1'];
				$return['state']        = 'PAID';
			}
			else if ( $_POST['amount3'] )
			{
				//--------------------------------------
				// Real subscription
				//--------------------------------------
				
				if ( $total_package_cost != $_POST['amount3'] )
				{
					$this->error = "Wrong upgrade subs amount. Looking for {$total_package_cost}, got {$_POST['amount3']}";
					return;
				}
				
				$return['amount_paid']  = $total_package_cost;
				$return['state']        = 'PAID';
			}
			else
			{
				//--------------------------------------
				// If all else fails..
				//--------------------------------------
				
				if ( $total_package_cost != $_POST['mc_gross'] AND $_POST['mc_gross'] != $balance_to_pay )
				{
					$this->error = "Wrong upgrade subs amount. Looking for {$total_package_cost}, got {$_POST['mc_gross']}";
					return;
				}
				
				$return['amount_paid']  = $_POST['mc_gross'];
				$return['state']        = 'PAID';
			}
			
		}
		else if ( $_POST['txn_type'] == 'subscr_failed' )
		{
			//--------------------------------------
			// Failed...
			//--------------------------------------
			
			$return['state'] = 'FAILED';
		}
		else if ( $_POST['txn_type'] == 'subscr_cancel' )
		{
			//--------------------------------------
			// Dead...
			//--------------------------------------
			
			$return['state'] = 'DEAD';
		}
		else if ( $_POST['txn_type'] == 'subscr_eot' )
		{
			//--------------------------------------
			// End of subscription
			//--------------------------------------
			
			$return['state'] = 'DEAD';
		}
		
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
		
		$this->db_info = array( 'human_title'         => 'PayPal',
								'human_desc'		  => 'All major credit cards accepted. See <a href="https://www.paypal.com" target="_blank">PayPal</a> for more information.',
								'module_name'         => $this->i_am,
								'allow_creditcards'   => 1,
								'allow_auto_validate' => 1,
								'default_currency'    => 'USD' );
							   
		
		$this->install_lang = array( 'gw_'.$this->i_am => "Click the button below to complete this order via PayPal's website. All costs will be converted into USD for PayPal's website" );
	}	
	
}

 
?>