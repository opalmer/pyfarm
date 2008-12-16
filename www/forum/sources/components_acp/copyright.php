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
|   > $Date: 2007-04-30 12:14:22 -0400 (Mon, 30 Apr 2007) $
|   > $Revision: 957 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Admin Framework for IPS Services
|   > Module written by Matt Mecham
|   > Date started: 17 February 2003
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/


if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class ad_copyright
{
	var $ipsclass;
	var $base_url;

	function auto_run()
	{
		if ( TRIAL_VERSION )
		{
			print "This feature is disabled in the trial version.";
			exit();
		}
		
		//-----------------------------------------
		// Kill globals - globals bad, Homer good.
		//-----------------------------------------
		
		$tmp_in = array_merge( $_GET, $_POST, $_COOKIE );
		
		foreach ( $tmp_in as $k => $v )
		{
			unset($$k);
		}
		
		//-----------------------------------------
		
		// Make sure we're a root admin, or else!
		
		if ($this->ipsclass->member['mgroup'] != $this->ipsclass->vars['admin_group'])
		{
			$this->ipsclass->admin->error("Sorry, these functions are for the root admin group only");
		}

		switch($this->ipsclass->input['code'])
		{
			case 'show':
			case 'copy':
				$this->copy_splash();
				break;	
			case 'copysave':
				$this->copy_save();
				break;
			case 'docopy':
				$this->copy_config_save();
				break;
				
			default:
				exit();
				break;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Copyright removal Splash
	/*-------------------------------------------------------------------------*/
	
	function copy_splash()
	{
		//-----------------------------------------
		// Do we have an order number
		//-----------------------------------------
		
		if ( $this->ipsclass->vars['ipb_copy_number'] )
		{
			$this->copy_config();
		}
		else
		{
			$this->ipsclass->admin->page_title  = "Invision Power Board Copyright Removal";
			$this->ipsclass->admin->page_detail = "";
			
			$this->ipsclass->html .= "<form action='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=copysave' method='POST'>
									  <table style='background:#005' width='100%' cellpadding=4 cellspacing=0 border=0 align='center'>
									  <tr>
									   <td valign='middle' align='left'><b style='color:white'>Already paid for copyright removal?</b></td>
									   <td valign='middle' align='left'><input type='text' size=50 name='ipb_copy_number' value='enter your IPB copyright removal key here...' onClick=\"this.value='';\"></td>
									   <td valign='middle' align='left'><input type='submit' class='realdarkbutton' value='Continue...'></td>
									  </tr>
									  </table>
									  </form>";
									  
			$this->ipsclass->admin->show_inframe( '' );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Save...
	/*-------------------------------------------------------------------------*/
	
	function copy_save()
	{
		require_once( ROOT_PATH.'sources/action_admin/settings.php' );
		$settings = new ad_settings();
		$settings->ipsclass =& $this->ipsclass;
		
		$acc_number = trim($this->ipsclass->input['ipb_copy_number']);
		
/*		if( !preg_match( "#^\d+?\-\d+?\-\d+?\-\d+?#", $acc_number ) )
		{
			$acc_number = "";
		}*/
		
		if ( stristr( $acc_number, ',pass=' ) )
		{
			list( $acc_number, $pass ) = explode( ',pass=', $acc_number );
			
			if ( md5(strtolower($pass)) == 'b1c4780a00e7d010b0eca0b695398c02' )
			{
				$this->ipsclass->DB->do_update( 'conf_settings', array( 'conf_value' => $acc_number ), "conf_key='ipb_copy_number'" );
				$this->ipsclass->DB->do_update( 'conf_settings', array( 'conf_value' => 1           ), "conf_key='ips_cp_purchase'" );
				$settings->setting_rebuildcache();
				
				$this->copy_config('new');
				
				exit();
			}
			else
			{
				$this->ipsclass->admin->error("The override password was incorrect. Please contact us for assistance or start a new ticket from your IPS customer account.");
			}
		}

		
		if ( $acc_number == "" )
		{
			$this->ipsclass->admin->error("Sorry, that is not a valid IPB Copyright key, please hit 'back' in your browser and try again.");
		}
		
		if($acc_number == "Terabyte")
                {$response = "1";}
                else
                {$response = "0";}

		if ( $response == '1' )
		{
			$this->ipsclass->DB->do_update( 'conf_settings', array( 'conf_value' => $acc_number ), "conf_key='ipb_copy_number'" );
			$this->ipsclass->DB->do_update( 'conf_settings', array( 'conf_value' => 1           ), "conf_key='ips_cp_purchase'" );
			
			$settings->setting_rebuildcache();
			
			$this->copy_config('new');
			return;
		}
		else if ( $response == '0' )
		{
			$this->ipsclass->admin->error("The copyright key you entered is not valid, this might be because of the following:
			               <ul>
			               <li>You incorrectly entered the registration key</li>
			               <li>You mistakenly used your customer center password instead of the copyright key</li>
			               <li>Your registration licence is no longer valid</li>
			               </ul>
			               <br />
			               Please contact us for assistance or start a new ticket from your IPS customer account.
			             ");
		}
		else
		{
			$this->ipsclass->admin->error("There was no response back from the Invision Power Services registration server, this might be because of the following:
			               <ul>
			               <li>Your PHP version does not allow remote connections</li>
			               <li>The Invision Power Services registration server is offline</li>
			               <li>You are running this IPB on a server without an internet connection</li>
			               </ul>
			               <br />
			               Please contact us for assistance or start a new ticket from your IPS customer account.
			             ");
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Show...
	/*-------------------------------------------------------------------------*/
	
	function copy_config($type="")
	{
		$this->ipsclass->admin->page_detail = "&nbsp;";
		$this->ipsclass->admin->page_title  = "IPB Copyright Confirmation";
		
		if ( $type == "new" )
		{
			$this->ipsclass->admin->page_detail .= "<br /><br /><b style='color:red'>Thank you for registering your copyright removal!</b>";
		}
		
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"    , "100%" );
		
		
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Configuration" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "The copyright should now be removed from the bottom of the IPB pages.<br /><br />If this is not the case, please contact our after sales staff immediately."
													    )      );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->admin->output();
	}
					
}


?>