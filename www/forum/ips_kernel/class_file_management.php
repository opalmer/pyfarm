<?php

/*
+---------------------------------------------------------------------------
|	Invision Power Board
|   ========================================
|   by Matthew Mecham
|   (c) 2004 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
+---------------------------------------------------------------------------
|   INVISION POWER BOARD IS NOT FREE SOFTWARE!
|   http://www.invisionboard.com
+---------------------------------------------------------------------------
|
|   > File Management Functions
|   > Script written by Matt Mecham
|   > Date started: Tuesday 22nd February 2005 (16:55) I have cold feet
|
+---------------------------------------------------------------------------
*/

/**
* Class File Management
*
* Wrapper for getting file contents
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
* Class File Management
*
* Wrapper for getting file contents
*
* @package	IPS_KERNEL
* @author   Matt Mecham
* @version	2.1
*/
class class_file_management
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
	* HTTP Status Code/Text
	*
	* @var array
	*/	
	var $http_status_code = 0;
	var $http_status_text = "";
	
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

	function class_file_management()
	{

	}
	
	/*-------------------------------------------------------------------------*/
	// Get file contents (accepts URL or path)
	// file_get_contents() has caching issues
	/*-------------------------------------------------------------------------*/
	
	/**
	* Get file contents (accepts URL or path)
	*
	* file_get_contents() has caching issues
	* @return	string	File data
	*/
	function get_file_contents( $file_location, $http_user='', $http_pass='' )
	{
		//-------------------------------
		// INIT
		//-------------------------------
		
		$contents      = "";
		$file_location = str_replace( '&amp;', '&', $file_location );
		
		//-----------------------------------------
		// Inline user/pass?
		//-----------------------------------------
		
		if ( $http_user and $http_pass )
		{
			$this->auth_req  = 1;
			$this->auth_user = $http_user;
			$this->auth_pass = $http_pass;
		}
		
		//-------------------------------
		// Hello
		//-------------------------------
		
		if ( ! $file_location )
		{
			return FALSE;
		}
		
		if ( ! stristr( $file_location, 'http://' ) AND ! stristr( $file_location, 'https://' ) )
		{
			//-------------------------------
			// It's a path!
			//-------------------------------
			
			if ( ! file_exists( $file_location ) )
			{
				$this->errors[] = "File '$file_location' does not exist, please check the path.";
				return;
			}
			
			$contents = $this->get_contents_with_fopen( $file_location );
		}
		else
		{
			//-------------------------------
			// Is URL - using GET?
			//-------------------------------
			
			if ( $this->use_sockets )
			{
				$contents = $this->get_contents_with_socket( $file_location );
			}
			else
			{
				$contents = $this->get_contents_with_fopen( $file_location );
			}
		}
		
		return $contents;
	}
	
	/*-------------------------------------------------------------------------*/
	// USE FOPEN TO GET TEXT
	/*-------------------------------------------------------------------------*/
	
	/**
	* Get file contents (with PHP's fopen)
	*
	* @return	string	File data
	*/
	function get_contents_with_fopen( $file_location )
	{
		//-------------------------------
		// INIT
		//-------------------------------
		
		$buffer = "";
		
		@clearstatcache();
			
		if ( $FILE = fopen( $file_location, "r") )
		{
			while ( ! feof( $FILE ) )
			{
			   $buffer .= fgets($FILE, 4096);
			}
			
			fclose($FILE);
		}
		
		return $buffer;
	}
	
	/*-------------------------------------------------------------------------*/
	// USE SOCKET TO GET TEXT
	/*-------------------------------------------------------------------------*/
	
	/**
	* Get file contents (with sockets)
	*
	* @return	string	File data
	*/
	function get_contents_with_socket( $file_location )
	{
		//-------------------------------
		// INIT
		//-------------------------------
		
		$data            = null;
		$fsocket_timeout = 10;
		
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
      	
      	if ( !empty( $url_parts["path"] ) )
		{
			$path = $url_parts["path"];
		}
		else
		{
			$path = "/";
		}
 
		if ( !empty( $url_parts["query"] ) )
		{
			$path .= "?" . $url_parts["query"];
		}
      	
      	//-------------------------------
      	// Open connection
      	//-------------------------------
      	
      	if ( ! $fp = @fsockopen( $host, $port, $errno, $errstr, $fsocket_timeout ) )
      	{
			$this->errors[] = "Could not establish a connection with $host";
			return FALSE;
         
		}
		else
		{
			$final_carriage = "";
			
			if ( ! $this->auth_req )
			{
				$final_carriage = "\r\n";
			}
			
			if ( ! fputs( $fp, "GET $path HTTP/1.0\r\nHost:$host\r\nConnection: Keep-Alive\r\n{$final_carriage}" ) )
			{
				$this->errors[] = "Unable to send request to $host!";
				return FALSE;
			}
			
			if ( $this->auth_req )
			{
				if ( $this->auth_user && $this->auth_pass )
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
         
         // HTTP/1.1 ### ABCD
         $this->http_status_code = substr( $data, 9, 3 );
         $this->http_status_text = substr( $data, 13, ( strpos( $data, "\r\n" ) - 13 ) );

         $tmp = split("\r\n\r\n", $data, 2);
         $data = $tmp[1];

 		return $data;
	}
	
	/*-------------------------------------------------------------------------*/
	// USE cURL TO GET TEXT
	/*-------------------------------------------------------------------------*/
	
	/**
	* Get file contents (with cURL)
	*
	* @return	string	File data
	*/
	function get_contents_with_curl( $file_location )
	{
		if ( function_exists( 'curl_init' ) AND function_exists("curl_exec") )
		{
			$ch = curl_init( $file_location );
			
			curl_setopt( $ch, CURLOPT_HEADER		 , 0);
			curl_setopt( $ch, CURLOPT_TIMEOUT        , 15 );
			curl_setopt( $ch, CURLOPT_POST           , 0 );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER , 1 ); 
			
			$data = curl_exec($ch);
			curl_close($ch);
			
			return $data ? $data : FALSE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// dir_copy_dir
	//
	// Copies to contents of a dir to a new dir, creating
	// destination dir if needed.
	//
	/*-------------------------------------------------------------------------*/
	
	function dir_copy_dir($from_path, $to_path, $mode = 0777)
	{
		$this->errors = "";
		
		//-----------------------------------------
		// Strip off trailing slashes...
		//-----------------------------------------
		
		$from_path = preg_replace( "#/$#", "", $from_path);
		$to_path   = preg_replace( "#/$#", "", $to_path);
	
		if ( ! is_dir( $from_path ) )
		{
			$this->errors[] = "Could not locate directory '$from_path'";
			return FALSE;
		}
	
		if ( ! is_dir( $to_path ) )
		{
			if ( ! @mkdir( $to_path, $mode ) )
			{
				$this->errors[] = "Could not create directory '$to_path' please check the CHMOD permissions and re-try";
				return FALSE;
			}
			else
			{
				@chmod( $to_path, $mode );
			}
		}
		
		if ( is_dir( $from_path ) )
		{
			$handle = opendir($from_path);
			
			while ( ($file = readdir($handle)) !== false )
			{
				if ( ($file != ".") && ($file != "..") )
				{
					if ( is_dir( $from_path."/".$file ) )
					{
						$this->dir_copy_dir($from_path."/".$file, $to_path."/".$file);
					}
					
					if ( is_file( $from_path."/".$file ) )
					{
						copy($from_path."/".$file, $to_path."/".$file);
						@chmod($to_path."/".$file, 0777);
					} 
				}
			}
			closedir($handle); 
		}
		
		if ( ! count( $this->errors ) )
		{
			return TRUE;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// dir_rm_dir
	//
	// Removes directories, if non empty, removes
	// content and directories
	/*-------------------------------------------------------------------------*/
	
	function dir_rm_dir($file)
	{
		$errors = 0;
		
		//-----------------------------------------
		// Remove trailing slashes..
		//-----------------------------------------
		
		$file = preg_replace( "#/$#", "", $file );
		
		if ( file_exists($file) )
		{
			//-----------------------------------------
			// Attempt CHMOD
			//-----------------------------------------
			
			@chmod($file, 0777);
			
			if ( is_dir($file) )
			{
				$handle = opendir($file);
				
				while ( ($filename = readdir($handle)) !== false )
				{
					if ( ($filename != ".") && ($filename != "..") )
					{
						$this->dir_rm_dir($file."/".$filename);
					}
				}
				
				closedir($handle);
				
				if ( ! @rmdir($file) )
				{
					$errors++;
				}
			}
			else
			{
				if ( ! @unlink($file) )
				{
					$errors++;
				}
			}
		}
		
		if ($errors == 0)
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
}

?>