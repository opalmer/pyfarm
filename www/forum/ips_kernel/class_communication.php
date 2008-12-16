<?php

/*
+---------------------------------------------------------------------------
|   Invision Power KERNEL
|	Invision Power Board
|   ========================================
|   by Matthew Mecham
|   (c) 2006 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
+---------------------------------------------------------------------------
|   INVISION POWER BOARD IS NOT FREE SOFTWARE!
|   http://www.invisionboard.com
+---------------------------------------------------------------------------
|
|   > Communication Class: Allows data to be sent via HTTP:POST
|   > Script written by Matt Mecham
|   > Date started: Friday 6th January 2006 (12:24)
|
+---------------------------------------------------------------------------
*/

/**
* Class Communication
*
* Class to send data via HTTP:POST and return data
*
* @package	IPS_KERNEL
* @author   Matt Mecham
* @version	2.1
*/

if ( ! defined( 'IPS_CLASSES_PATH' ) )
{
	/**
	* Define classes path
	*/
	define( 'IPS_CLASSES_PATH', dirname(__FILE__) );
}

/**
* Class Communication
*
* Wrapper for getting file contents
*
* @package	IPS_KERNEL
* @author   Matt Mecham
* @version	2.1
*/
class class_communication
{
	/**
	* Use sockets flag
	*
	* @var integer
	*/
	var $use_sockets = 0;
	
	/**
	* Error array
	*
	* @var array
	*/
	var $errors      = array();
	
	/**
	* Key prefix
	* Prefix to identify communication strings
	*
	* @var string
	*/
	var $key_prefix = '__xsx__';
	
	/**#@+
	* Set Authentication
	*
	* @var strings 
	*/
	var $auth_req       = 0;
	var $auth_user;
	var $auth_pass;
	/**#@-*/	
	
	/*-------------------------------------------------------------------------*/
	// Constructor
	/*-------------------------------------------------------------------------*/

	function class_communication()
	{

	}
	
	/*-------------------------------------------------------------------------*/
	// RECEIVE DATA
	/*-------------------------------------------------------------------------*/
	/**
	* Receive data from the "send" function
	*
	* @param	array	Array of fields to return
	* @return	array	Array of fields
	*/
	function communication_receive_data( $return_fields=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$return_array = array();
		
		//-----------------------------------------
		// Get data...
		//-----------------------------------------
		
		foreach( $_REQUEST as $k => $v )
		{
			if ( strstr( $k, $this->key_prefix ) )
			{
				$k = str_replace( $this->key_prefix, '', $k );
				
				$return_array[ $k ] = $v;
			}
		}
		
		return $this->_filter_fields( $return_array );
	}
	
	/*-------------------------------------------------------------------------*/
	// SEND DATA
	/*-------------------------------------------------------------------------*/
	/**
	* Send the data
	*
	* @param	string	URI to post to
	* @param	array   Arry of post fields
	* @return	string	File data
	*/
	function communication_send_data( $file_location='', $post_array=array() )
	{
		if ( ! is_array( $post_array ) OR ! count( $post_array ) )
		{
			return FALSE;
		}
		
		if ( ! $file_location )
		{
			return FALSE;
		}
		
		return $this->_post_data( $file_location, $post_array );
	}
	
	/*-------------------------------------------------------------------------*/
	// Filter off fields
	/*-------------------------------------------------------------------------*/
	/**
	* Filter out fields (optional)
	*
	* @access	private
	* @param	array	Array of fields to return
	* @return	array	Array of fields
	*/
	function _filter_fields( $in_fields=array(), $out_fields=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$return_array = array();
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! is_array( $in_fields ) or ! count( $in_fields ) )
		{
			return FALSE;
		}
		
		if ( ! is_array( $out_fields ) or ! count( $out_fields ) )
		{
			return $in_fields;
		}
		
		//-----------------------------------------
		// Get data...
		//-----------------------------------------
		
		foreach( $out_fields as $k => $type )
		{
			if ( $in_fields[ $k ] )
			{
				switch ( $type )
				{
					default:
					case 'string':
					case 'text':
						$return_array[ $k ] = trim( $in_fields[ $k ] );
						break;
					case 'int':
					case 'integar':
						$return_array[ $k ] = intval( $in_fields[ $k ] );
						break;
					case 'float':
					case 'floatval':
						$return_array[ $k ] = floatval( $in_fields[ $k ] );
						break;
				}
			}
		}
		
		return $return_array;
	}
	
	/*-------------------------------------------------------------------------*/
	// SEND DATA
	/*-------------------------------------------------------------------------*/
	/**
	* Get file contents (with sockets)
	*
	* @access	private
	* @param	string	URI to post to
	* @param	array   Arry of post fields
	* @return	string	File data
	*/
	function _post_data( $file_location, $post_array )
	{
		//-------------------------------
		// INIT
		//-------------------------------
		
		$data            = null;
		$fsocket_timeout = 10;
		$post_back       = array();
		
		//-------------------------------
		// Fix up post string
		//-------------------------------
		
		foreach ( $post_array as $key => $val )
		{
			$post_back[] = $this->key_prefix . $key . '=' . urlencode($val);
		}
		
		$post_back_str = implode('&', $post_back);
		
		//-------------------------------
		// Parse URL
		//-------------------------------
		
		$url_parts = parse_url($file_location);
		
		if ( ! $url_parts['host'] )
		{
			$this->errors[] = "No host found in the URL '$file_location'!";
			return FALSE;
		}
		
		//-------------------------------
		// Finalize
		//-------------------------------
		
		$host = $url_parts['host'];
      	$port = ( isset($url_parts['port']) ) ? $url_parts['port'] : 80;
      	
      	//-------------------------------
      	// Tidy up path
      	//-------------------------------
      	
      	if ( ! empty( $url_parts["path"] ) )
		{
			$path = $url_parts["path"];
		}
		else
		{
			$path = "/";
		}
 
		if ( ! empty( $url_parts["query"] ) )
		{
			$path .= "?" . $url_parts["query"];
		}
		
		//-------------------------------
      	// Try CURL first...
      	//-------------------------------

		if ( function_exists("curl_init") AND function_exists("curl_exec") )
		{
			if ( $sock = curl_init() )
			{
				curl_setopt( $sock, CURLOPT_URL            , $file_location );
				curl_setopt( $sock, CURLOPT_TIMEOUT        , 15 );
				curl_setopt( $sock, CURLOPT_POST           , TRUE );
				curl_setopt( $sock, CURLOPT_POSTFIELDS     , $post_back_str );
				curl_setopt( $sock, CURLOPT_POSTFIELDSIZE  , 0);
				curl_setopt( $sock, CURLOPT_RETURNTRANSFER , TRUE ); 
		
				$result = curl_exec($sock);
				
				curl_close($sock);
				
				return $result ? $result : FALSE;
			}
		}
      	else
		{
      
  	    	if ( ! $fp = @fsockopen( $host, $port, $errno, $errstr, $fsocket_timeout ) )
	      	{
				$this->errors[] = "CONNECTION REFUSED FROM $host";
				return FALSE;
         
			}
			else
			{
				$final_carriage = "";
				
				if ( ! $this->auth_req )
				{
					$final_carriage = "\r\n";
				}
				
				$header  = "POST $path HTTP/1.0\r\n";
				$header .= "Host: $host\r\n";
				$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
				$header .= "Content-Length: " . strlen($post_back_str) . "\r\n{$final_carriage}";
				
				if ( ! fputs( $fp, $header . $post_back_str ) )
				{
					$this->errors[] = "Unable to send request to $host!";
					return FALSE;
				}
			
				if ( $this->auth_req )
				{
					if( $this->auth_user && $this->auth_pass )
					{
						$header = "Authorization: Basic ".base64_encode("{$this->auth_user}:{$this->auth_pass}")."\r\n\r\n";
						
						if ( ! fputs( $fp, $header ) )
						{
							$this->errors[] = "Authorization Failed!";
							return FALSE;
						}
					}
				}				
	         }

	         @stream_set_timeout($fp, $fsocket_timeout);
         	 
	         $status = @socket_get_status($fp);
         
	         while( ! feof($fp) && ! $status['timed_out'] )         
	         {
	            $data .= fgets ($fp,8192);
	            $status = socket_get_status($fp);
	         }
         
	         fclose ($fp);
         
	         //-------------------------------
	         // Strip headers
	         //-------------------------------
         
	         $tmp = split("\r\n\r\n", $data, 2);
	         $data = $tmp[1];

	 		return $data;
		}
	}
	
}

?>