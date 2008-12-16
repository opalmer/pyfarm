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
|   > $Date: 2005-10-10 14:03:20 +0100 (Mon, 10 Oct 2005) $
|   > $Revision: 22 $
|   > $Author: matt $
+---------------------------------------------------------------------------
|
|   > Converge methods (KERNEL)
|   > Module written by Matt Mecham
|   > Date started: 15th March 2004
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/

/**
* IPS Kernel Pages: Converge
*
* @package IPS_KERNEL
* @author   Matt Mecham
* @version	2.1
*/
/**
* Converge Class
*
* Methods and functions for handling converge authentication,
* password generation and update methods
*
* @package IPS_KERNEL
* @author   Matt Mecham
* @version	2.1
*/
class class_converge
{
	/**
	* Current DB connection, passed by reference
	*
	* @var	Database object
	* @todo Use ipsclass?
	*/
	var $current_db = "";
	
	/**
	* Converge member array
	*
	* @var array
	*/
	var $member     = array();
	
	/*-------------------------------------------------------------------------*/
	// CONSTRUCTOR
	/*-------------------------------------------------------------------------*/
	
	/**
	* Constructor, accepts database object
	*
	* @param	object Database object
	*/
	
	function class_converge(&$DB)
	{
		$this->current_db = $DB;
		
		// Temp code!
		$this->converge_db = $DB;
	}
	
	/*-------------------------------------------------------------------------*/
	// Test for converge row
	/*-------------------------------------------------------------------------*/
	
	/**
	* Checks for a DB row that matches $email
	*
	* @param	string Email address
	* @return	boolean
	*/
	
	function converge_check_for_member_by_email( $email )
	{
		$test = $this->converge_db->simple_exec_query( array( 'select' => 'converge_id', 'from' => 'members_converge', 'where' => "converge_email='$email'" ) );
		
		if ( $test['converge_id'] )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Update converge row (password)
	/*-------------------------------------------------------------------------*/
	
	/**
	* Updates member's converge DB row password
	*
	* @param	string	MD5 hash of new password
	* @param	string	Email address
	*/
	
	function converge_update_password( $new_md5_pass, $email )
	{
		if ( ! $email or ! $new_md5_pass )
		{
			return FALSE;
		}
		
		if ( $email != $this->member['converge_email'] )
		{
			$temp_member = $this->converge_db->simple_exec_query( array( 'select' => '*', 'from' => 'members_converge', 'where' => "converge_email='$email'" ) );
		}
		else
		{
			$temp_member = $this->member;
		}
		
		$new_pass = md5( md5( $temp_member['converge_pass_salt'] ) . $new_md5_pass );
		
		$this->converge_db->do_update( 'members_converge', array( 'converge_pass_hash' => $new_pass ), 'converge_id='.$temp_member['converge_id'] );
	}
	
	/*-------------------------------------------------------------------------*/
	// Update converge row
	/*-------------------------------------------------------------------------*/
	
	/**
	* Updates member's converge DB row email address
	*
	* @param	string	Current email address
	* @param	string	New email address
	* @return	boolean
	*/
	
	function converge_update_member($curr_email, $new_email)
	{
		if ( ! $curr_email or ! $new_email )
		{
			return FALSE;
		}
		
		if ( ! $this->member['converge_id'] )
		{
			$this->converge_load_member( $curr_email );
			
			if ( ! $this->member['converge_id'] )
			{
				return FALSE;
			}
		}
		
		$this->converge_db->do_update( 'members_converge', array( 'converge_email' => $new_email ), 'converge_id='.$this->member['converge_id'] );
		
		return TRUE;
	}
	
	/*-------------------------------------------------------------------------*/
	// Get converge row
	/*-------------------------------------------------------------------------*/
	
	/**
	* Load converge DB row by email address
	*
	* @param	string	Current email address
	*/
	
	function converge_load_member($email)
	{
		if ( ! $email )
		{
			$this->member = array();
		}
		else
		{
			$this->member = $this->converge_db->simple_exec_query( array( 'select' => '*', 'from' => 'members_converge', 'where' => "converge_email='$email'" ) );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Get converge row (based on ID)
	/*-------------------------------------------------------------------------*/
	
	/**
	* Load converge DB row by converge (member) ID
	*
	* @param	integar	Member ID
	*/
	
	function converge_load_member_by_id($id)
	{
		$id = intval($id);
		
		if ( ! $id )
		{
			$this->member = array();
		}
		else
		{
			$this->member = $this->converge_db->simple_exec_query( array( 'select' => '*', 'from' => 'members_converge', 'where' => "converge_id='$id'" ) );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Authenticate password
	/*-------------------------------------------------------------------------*/
	
	/**
	* Check supplied password with converge DB row
	*
	* @param	string	MD5 of entered password
	* @return	boolean
	*/
	
	function converge_authenticate_member( $md5_once_password )
	{
		if ( ! $this->member['converge_pass_hash'] )
		{
			return FALSE;
		}
		
		if ( $this->member['converge_pass_hash'] == $this->generate_compiled_passhash( $this->member['converge_pass_salt'], $md5_once_password ) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Generate password
	/*-------------------------------------------------------------------------*/
	
	/**
	* Generates a compiled passhash
	*
	* Returns a new MD5 hash of the supplied salt and MD5 hash of the password
	*
	* @param	string	User's salt (5 random chars)
	* @param	string	User's MD5 hash of their password
	* @return	string	MD5 hash of compiled salted password
	*/
	
	function generate_compiled_passhash($salt, $md5_once_password)
	{
		return md5( md5( $salt ) . $md5_once_password );
	}
	
	/*-------------------------------------------------------------------------*/
	// Generate SALT
	/*-------------------------------------------------------------------------*/
	
	/**
	* Generates a password salt
	*
	* Returns n length string of any char except backslash
	*
	* @param	integer	Length of desired salt, 5 by default
	* @return	string	n character random string
	*/
	
	function generate_password_salt($len=5)
	{
		$salt = '';
		
		//srand( (double)microtime() * 1000000 );
		// PHP 4.3 is now required ^ not needed
		
		for ( $i = 0; $i < $len; $i++ )
		{
			$num   = rand(33, 126);
			
			if ( $num == '92' )
			{
				$num = 93;
			}
			
			$salt .= chr( $num );
		}
		
		return $salt;
	}
	
	/*-------------------------------------------------------------------------*/
	// Generate auto log in key (MD5 hash of random 60 char string
	/*-------------------------------------------------------------------------*/
	
	/**
	* Generates a log in key
	*
	* @param	integer	Length of desired random chars to MD5
	* @return	string	MD5 hash of random characters
	*/
	
	function generate_auto_log_in_key($len=60)
	{
		$pass = $this->generate_password_salt( $len );
		
		return md5($pass);
	}
	

	
	
}

?>