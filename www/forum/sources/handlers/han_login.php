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
|   > $Date: 2007-03-23 17:26:46 -0400 (Fri, 23 Mar 2007) $
|   > $Revision: 904 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Post Handler
|   > Module written by Matt Mecham
|   > Date started: Wednesday 9th March 2005 (15:23)
|
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class han_login
{
	# Global
	var $ipsclass;
	
	var $module;
	
	var $is_admin_auth 	= 0;
	var $return_code   	= 'WRONG_AUTH';
	var $return_details	= "";
	var $account_unlock = 0;
	var $member        	= array( 'id' => 0 );
	
	# Work
	var $login_method  	= array();
	var $login_conf    	= array();
	
    /*-------------------------------------------------------------------------*/
    // INIT
    /*-------------------------------------------------------------------------*/
    
    function init()
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	$class = "";
    	$conf  = "";
    	
    	//-----------------------------------------
    	// Load DB info
    	//-----------------------------------------
    	
    	$this->login_method = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'login_methods', 'where' => 'login_enabled=1' ) );
    	
    	//$this->login_method['login_folder_name'] = 'external';
    	
    	$class = ROOT_PATH.'sources/loginauth/'.$this->login_method['login_folder_name'].'/auth.php';
    	$conf  = ROOT_PATH.'sources/loginauth/'.$this->login_method['login_folder_name'].'/conf.php';
    	
    	//-----------------------------------------
    	// Got nothing?
    	//-----------------------------------------
    	
    	if ( ! $this->login_method['login_id'] OR ! file_exists( $class ) )
    	{
    		$this->login_method = array( 'login_title'        => 'Internal',
    									 'login_folder_name'  => 'internal',
    									 'login_allow_create' => 0 );
    									 
    		$class = ROOT_PATH.'sources/loginauth/internal/auth.php';
    		$conf  = ROOT_PATH.'sources/loginauth/internal/conf.php';
    	}
    	
    	//-----------------------------------------
    	// Attempt to load class
    	//-----------------------------------------
    	
    	if ( file_exists( $class ) )
    	{
    		require_once( ROOT_PATH . "sources/loginauth/login_core.php" );
    		require_once( $class );
    		$this->module           =  new login_method();
    		$this->module->ipsclass =& $this->ipsclass;
    	}
    	else
    	{
    		fatal_error( "CANNOT LOCATE LOG IN FILE FOR {$this->login_method['login_folder_name']}" );
    		exit();
    	}
    	
    	//-----------------------------------------
    	// Get conf
    	//-----------------------------------------
    	
    	if ( file_exists( $conf ) )
    	{
    		require_once( $conf );
    		$this->login_conf = $LOGIN_CONF;
    	}
    	
		//-----------------------------------------
		// Set up
		//-----------------------------------------
		
		$this->module->is_admin_auth      = $this->is_admin_auth;
		$this->module->allow_create       = $this->login_method['login_allow_create'];
		$this->module->login_method       = $this->login_method;
		$this->module->login_conf         = $this->login_conf;
    }
    
    /*-------------------------------------------------------------------------*/
    // PASSWORD CHECK
    /*-------------------------------------------------------------------------*/
  
  	function login_password_check( $username, $password )
  	{
  		$this->module->allow_create = 0;
  		
  		if ( $this->login_method['login_folder_name'] != 'internal' AND $this->login_method['login_type'] == 'onfail' )
  		{
  			# Attempt local-auth first
  			$this->module->auth_local( $username, $password );
  			
  			# Success? Return...
  			if ( $this->module->return_code == 'SUCCESS' )
  			{
  				$this->return_code = $this->module->return_code;
  				$this->member      = $this->module->member;
  				return;
  			}
  			# No? attempt remote auth
  			else
  			{
  				$this->module->authenticate( $username, $password );
  				$this->return_code = $this->module->return_code == 'SUCCESS' ? 'SUCCESS' : 'FAIL';
  				$this->member      = $this->module->member;
  			}
  		}
  		else
  		{
  			$this->module->authenticate( $username, $password );
  			$this->return_code = $this->module->return_code == 'SUCCESS' ? 'SUCCESS' : 'FAIL';
  			$this->member      = $this->module->member;
  		}
  	}
    
    /*-------------------------------------------------------------------------*/
    // AUTHENTICATE
    /*-------------------------------------------------------------------------*/
  
  	function login_authenticate( $username, $password )
  	{
  		//-----------------------------------------
  		// On fail?
  		//-----------------------------------------
  		
  		if ( $this->login_method['login_folder_name'] != 'internal' AND $this->login_method['login_type'] == 'onfail' )
  		{
  			# Attempt local-auth first
  			$this->module->auth_local( $username, $password );
  			
  			# Success? Return...
  			if ( $this->module->return_code == 'SUCCESS' )
  			{
  				$this->return_code = $this->module->return_code;
  				$this->member      = $this->module->member;
  				return;
  			}
  			# No? attempt remote auth
  			else
  			{
  				$this->module->authenticate( $username, $password );
  				$this->return_code = $this->module->return_code;
  				$this->member      = $this->module->member;
  			}
  		}
  		else
  		{
  			$this->module->authenticate( $username, $password );
  			$this->return_code 		= $this->module->return_code;
  			$this->account_unlock 	= $this->module->account_unlock;
  			$this->member      		= $this->module->member;
  		}
  	}
  	
  	
    /*-------------------------------------------------------------------------*/
    // Check for existing email
    /*-------------------------------------------------------------------------*/
  
  	function email_exists_check( $email )
  	{
	  	if( method_exists( $this->module, 'email_exists_check' ) )
	  	{
		  	$this->module->email_exists_check( $email );

		  	$this->return_code = $this->module->return_code;
		  	return;
	  	}
	  	else
	  	{
		  	$this->return_code = 'METHOD_NOT_DEFINED';
	  	}
  	}
  	
    /*-------------------------------------------------------------------------*/
    // Change email
    /*-------------------------------------------------------------------------*/
  
  	function change_email( $old_email, $new_email )
  	{
	  	if( method_exists( $this->module, 'change_email' ) )
	  	{
		  	$this->module->change_email( $old_email, $new_email );

		  	$this->return_code = $this->module->return_code;
		  	return;
	  	}
	  	else
	  	{
		  	$this->return_code = 'METHOD_NOT_DEFINED';
	  	}
  	}
  	
    /*-------------------------------------------------------------------------*/
    // Change email
    /*-------------------------------------------------------------------------*/
  
  	function change_pass( $email, $new_pass )
  	{
	  	if( method_exists( $this->module, 'change_pass' ) )
	  	{
		  	$this->module->change_pass( $email, $new_pass );

		  	$this->return_code = $this->module->return_code;
		  	return;
	  	}
	  	else
	  	{
		  	$this->return_code = 'METHOD_NOT_DEFINED';
	  	}
  	}
  	
    /*-------------------------------------------------------------------------*/
    // Create Account
    /*-------------------------------------------------------------------------*/
  
  	function create_account( $member=array() )
  	{
	  	if( !is_array( $member ) )
	  	{
		  	$this->return_code = 'FAIL';
		  	return;
	  	}
	  	
	  	if( method_exists( $this->module, 'create_account' ) )
	  	{
		  	$this->module->create_account( $member );

		  	$this->return_code 		= $this->module->return_code;
		  	$this->return_details	= $this->module->return_details;
		  	return;
	  	}
	  	else
	  	{
		  	$this->return_code = 'METHOD_NOT_DEFINED';
	  	}
  	}
}

?>