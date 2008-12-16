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
|   INVISION POWER BOARD IS NOT FREE SOFTWARE!
|   http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
|
|   > CORE Gateway Files
|   > Script written by Matt Mecham
|   > Date started: 31st March 2005 (14:45)
|
+--------------------------------------------------------------------------
*/

//---------------------------------------
// Security check
//---------------------------------------
		
if ( ! defined( 'GW_CORE_INIT' ) )
{
	print "You cannot access this module in this manner";
	exit();
}

class class_gateway
{
	# Global
	var $ipsclass;
	
	var $hidden_fields = array();
	
	var $debug;
	var $debug_file;
	
	var $payment_status;
	var $days_to_seconds;
	
	/*-------------------------------------------------------------------------*/
	// CONSTRUCTOR
	/*-------------------------------------------------------------------------*/
	
	function class_gateway()
	{
		//--------------------------------------
		// Debug?
		//--------------------------------------
		
		if ( defined( 'GW_DEBUG_MODE_ON' ) AND GW_DEBUG_MODE_ON )
		{
			$this->debug      = TRUE;
			$this->debug_file = ROOT_PATH.'cache/paysubs-'.time().'.php';
		}
		
		$this->payment_status = array(
									 'Completed' => 'paid',
									 'Failed'    => 'failed',
									 'Denied'    => 'failed',
									 'Refunded'  => 'failed'
									);
								
		$this->day_to_seconds = array( 'd' => 86400,
									   'w' => 604800,
									   'm' => 2592000,
									   'y' => 31536000,
									 );
	}
	
	/*-------------------------------------------------------------------------*/
	// Add hidden field
	/*-------------------------------------------------------------------------*/
	
	function core_add_hidden_field( $field, $value )
	{
		//--------------------------------------
		// Check..
		//--------------------------------------
		
		if ( ! $field )
		{
			return '';
		}
		
		//--------------------------------------
		// Add it
		//--------------------------------------
		
		$this->hidden_fields[] = "<input type='hidden' name='{$field}' value='{$value}' />";
	}
	
	/*-------------------------------------------------------------------------*/
	// Compile hidden field
	/*-------------------------------------------------------------------------*/
	
	function core_compile_hidden_fields()
	{
		//--------------------------------------
		// Check..
		//--------------------------------------
		
		return implode( "\n", $this->hidden_fields );
	}
	
	/*-------------------------------------------------------------------------*/
	// Clear hidden field
	/*-------------------------------------------------------------------------*/
	
	function core_clear_hidden_fields()
	{
		//--------------------------------------
		// Check..
		//--------------------------------------
		
		$this->hidden_fields = array();
	}

	/*-------------------------------------------------------------------------*/
	// Post back to gateway: urls= array( curl_full, sock_url sock_path )
	/*-------------------------------------------------------------------------*/

	function core_post_back( $urls=array(), $post_back_str="", $port=80 )
	{
		//--------------------------------------
		// INIT
		//--------------------------------------
		
		$curl_used     = 0;
		$result        = "";
		
		//--------------------------------------
		// Got a post back string?
		//--------------------------------------
		
		if ( ! $post_back_str )
		{
			foreach ($_POST as $key => $val)
			{
				$post_back[] = $key . '=' . urlencode ($val);
			}
			
			$post_back_str = implode('&', $post_back);
		}
		
		//--------------------------------------
		// Attempt CURL
		//--------------------------------------
		
		if ( function_exists("curl_init") AND function_exists("curl_exec") )
		{
			if ( $sock = curl_init() )
			{
				curl_setopt( $sock, CURLOPT_URL            , $urls['curl_full'] );
				curl_setopt( $sock, CURLOPT_TIMEOUT        , 15 );
				curl_setopt( $sock, CURLOPT_POST           , TRUE );
				curl_setopt( $sock, CURLOPT_POSTFIELDS     , $post_back_str );
				curl_setopt( $sock, CURLOPT_POSTFIELDSIZE  , 0);
				curl_setopt( $sock, CURLOPT_RETURNTRANSFER , TRUE ); 
		
				$result = curl_exec($sock);
				
				curl_close($sock);
				
				if ($result !== FALSE)
				{
					$curl_used = 1;
				}
			}
		}
		
		//--------------------------------------
		// Not got a result?
		//--------------------------------------
		
		if ( ! $curl_used )
		{
			$header  = "POST {$urls['sock_path']} HTTP/1.0\r\n";
			$header .= "Host: {$urls['sock_url']}\r\n";
			$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
			$header .= "Content-Length: " . strlen($post_back_str) . "\r\n\r\n";
			
			if ( $fp = fsockopen( $urls['sock_url'], $port, $errno, $errstr, 30 ) )
			{
				socket_set_timeout($fp, 30);
				
				fwrite($fp, $header . $post_back_str);
				
				while ( ! feof($fp) )
				{
					$result .= fgets($fp, 1024);
				}
				
				fclose($fp);
			}
		}
		
		if ( defined( 'GW_DEBUG_MODE_ON' ) AND GW_DEBUG_MODE_ON )
		{
			$to_write = $result.'\n\n'.$post_back_str.'\n\n';
			
			foreach( $_POST as $k => $v )
			{
				$to_write .= "{$k}: {$v}\n";
			}
			
			$this->_write_debug_message( $to_write );
		}
		
				
		//--------------------------------------
		// Return...
		//--------------------------------------
		
		return $result;
	}

	/*-------------------------------------------------------------------------*/
	// Core: print status message
	/*-------------------------------------------------------------------------*/
	
	function core_print_status_message()
	{
		if ( strstr( php_sapi_name(), 'cgi' ) )
		{
			@header('Status: 200 OK');
		}
		else
		{
			@header('HTTP/1.1 200 OK');
		}
	}
		
	/*-------------------------------------------------------------------------*/
	// Append debug file
	/*-------------------------------------------------------------------------*/
	
	function _write_debug_message( $message )
	{
		//--------------------------------------
		// Check
		//--------------------------------------
		
		if ( ! $this->debug OR ! $this->debug_file )
		{
			return;
		}
		
		//--------------------------------------
		// INIT message
		//--------------------------------------
		
		$bars     = '----------------------------------------------------------------------------';
		$date_now = date( 'F j, Y, g:i a' );
		
		$msg_to_write = $bars."\n"."Date: ".$date_now."\n"."Gateway: ".$this->i_am."\n".$bars."\n".$message;
		
		if ( $FH = @fopen( $this->debug_file, 'a+' ) )
		{
			@fwrite( $FH, $msg_to_write, strlen( $msg_to_write ) );
			@fclose( $FH );
		}
	}
	
	
}

 
?>