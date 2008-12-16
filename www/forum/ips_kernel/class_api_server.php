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
|   > API SERVER: Send and receive API data
|   > Script written by Matt Mecham
|   > Date started: Friday 6th January 2006 (12:24)
|
+---------------------------------------------------------------------------
*/

/**
* Class API SERVER
*
* Class to send and receive API data
* <code>
* # APPLICATION: SEND AN API REQUEST AND PARSE DATA
* $api_server->api_send_request( 'http://www.domain.com/xmlrpc.php', 'get_members', array( 'name' => 'matt', 'email' => 'matt@email.com' ) );
* # APPLICATION: PICK UP REPLY AND PARSE
* print_r( $api_server->params );
*
* # SERVER: PARSE DATA, MAKE DATA AND RETURN
* $api_server->api_decode_request( $_SERVER['RAW_HTTP_POST_DATA'] );
* print $api_server->method_name;
* print_r( $api_server->params );
* # SERVER: SEND DATA BACK
* # Is complex array, so we choose to encode and send with the , 1 flag
* $api_server->api_send_reply( array( 'matt' => array( 'email' => 'matt@email.com', 'joined' => '01-01-2005' ) ), 1 );
* </code>
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
* Class API Server
*
* Easy method of getting and sending data
*
* @package	IPS_KERNEL
* @author   Matt Mecham
* @version	2.1
*/
class class_api_server
{
	/**
	* XML-RPC class
	* @var object
	*/
	var $xmlrpc;
	
	/**
	* XML-RPC serialized 64 key
	* @var string
	*/
	var $serialized_key = '__serialized64__';
	
	/**
	* Server function object
	* @var object
	*/
	var $xml_server;
	
	/**
	* Return cookie information
	* @var	array
	*/
	var $cookies = array();
	
	/**
	* XML-RPC cookie serialized 64 key
	* @var string
	*/
	var $cookie_serialized_key = '__cookie__serialized64__';
	
	/**
	* HTTP Auth required
	*/
	var $auth_user = '';
	var $auth_pass = '';
	
	/**
	* Errors array
	* @var array
	*/
	var $errors = array();
	
	/*-------------------------------------------------------------------------*/
	// Constructor
	/*-------------------------------------------------------------------------*/

	function class_api_server()
	{
		if ( ! is_object( $this->xmlrpc ) )
		{
			require_once( IPS_CLASSES_PATH . '/class_xml_rpc.php' );
			$this->xmlrpc = new class_xml_rpc();
		}
		
		$this->cookies = array();
	}
	
	/*-------------------------------------------------------------------------*/
    // MIMIC SOAP SERVER: Decode request
    /*-------------------------------------------------------------------------*/
    /**
    * Add object map to this class
    *
    * @param    string    Incoming data
    * @return   string    api
    */
    function decode_request( $incoming='' )
    {
        if ( ! $incoming )
        {
	        # PHP Bug: http://bugs.php.net/bug.php?id=41293
	        
			if( phpversion() == "5.2.2" ) 
			{
				$GLOBALS['HTTP_RAW_POST_DATA'] = file_get_contents( "php://input" );
			}
			
            $incoming = isset( $GLOBALS['HTTP_RAW_POST_DATA'] ) ? $GLOBALS['HTTP_RAW_POST_DATA'] : '';
        }

        //-----------------------------------------
        // Get data and dispatch
        //-----------------------------------------

        $this->api_decode_request( $incoming );

		$this->raw_request = $incoming;
		
        $api_call = explode( ".", $this->method_name );
        
		if ( count($api_call) > 1 )
        {
            $this->method_name = $api_call[1];

            return $api_call[0];
        }
        else
        {
            return "default";
        }
    }
	
	/*-------------------------------------------------------------------------*/
	// MIMIC SOAP SERVER: Add object map
	/*-------------------------------------------------------------------------*/
	/**
	* Add object map to this class
	*
	* @param	object	Server class object
	* @param	string	Document type
	* @return	boolean
	*/
	function add_object_map( $server_class, $doc_type='UTF-8' )
	{
		$this->xmlrpc->doc_type = $doc_type;
		
		if ( is_object( $server_class ) )
		{
			$this->xml_server =& $server_class;
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// MIMIC SOAP SERVER: get_xmlRPC
	/*-------------------------------------------------------------------------*/
	/**
	* Add object map to this class
	*
	* @param	string	Incoming data
	* @return	boolean
	*/
	function get_xml_rpc( $incoming='' )
	{
		if ( ! $this->xml_server )
		{
			return FALSE;
		}
		
		//-----------------------------------------
		// Got function?
		//-----------------------------------------
		
		if ( $this->method_name AND is_array( $this->xml_server->__dispatch_map[ $this->method_name ] ) )
		{
			$func    = $this->method_name;
			$_params = array();
			
			//-----------------------------------------
			// Figure out params to use...
			//-----------------------------------------
			
			if ( is_array( $this->params ) and is_array( $this->xml_server->__dispatch_map[ $func ]['in'] ) )
			{
				foreach( $this->xml_server->__dispatch_map[ $func ]['in'] as $field => $type )
				{
					$_var = $this->params[ $field ];
					
					switch ($type)
					{
						default:
						case 'string':
							$_var = (string) $_var;
							break;
		                case 'int':
		 				case 'i4':
							$_var = (int)    $_var;
							break;
		                case 'double':
							$_var = (double) $_var; 
							break;
		                case 'boolean':
							$_var = (bool)   $_var;
							break;
						case 'base64':
							$_var = trim($_var);
							break;
						case 'struct':
							$_var = is_array($_var) ? $_var : (string) $_var;
							break;
		            }
		
					$_params[ $field ] = $_var;
				}
			}
			
			if ( is_array( $_params ) )
			{
				@call_user_func_array( array( &$this->xml_server, $func), $_params );
			}
			else
			{
				@call_user_func( array( &$this->xml_server, $func), $_params );
			}
		}
		else
		{
			//-----------------------------------------
			// Return false
			//-----------------------------------------
			
			$this->api_send_error( 100, 'No methodRequest function -' . htmlspecialchars( $this->method_name ) . ' defined / found' );
			exit();
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Add cookies
	/*-------------------------------------------------------------------------*/
	/**
	* Return API Request
	*
	* @param	array	Array of params to send
	* @param	int  	Complex data: Encode before sending
	* @return	nuffink
	*/
	function api_add_cookie_data( $data )
	{
		if ( $data['name'] )
		{
			$this->cookies[ $data['name'] ] = $data;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Return API Request
	/*-------------------------------------------------------------------------*/
	/**
	* Return API Request
	*
	* @param	array	Array of params to send
	* @param	int  	Complex data: Encode before sending
	* @return	nuffink
	*/
	function api_send_reply( $data=array(), $complex_data=0, $force=array() )
	{
		//-----------------------------------------
		// Cookies?
		//-----------------------------------------
		
		if ( is_array( $this->cookies ) AND count( $this->cookies ) )
		{
			$data[ $this->cookie_serialized_key ] = $this->encode_base64_array( $this->cookies );
			$this->xmlrpc->map_type_to_key[ $this->cookie_serialized_key ] = 'base64';
		}
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! is_array( $data ) )
		{
			$this->xmlrpc->xml_rpc_return_value( $data );
		}
		elseif ( ! count( $data ) )
		{
			# No data? Just return true
			$this->xmlrpc->xml_rpc_return_true();
		}
		
		//-----------------------------------------
		// Complex data?
		//-----------------------------------------
		
		if ( $complex_data )
		{
			$_tmp = $data;
			$data = array();
			$data[ $this->serialized_key ] = $this->encode_base64_array( $_tmp );
			$this->xmlrpc->map_type_to_key[ $this->serialized_key ] = 'base64';
		}
		
		//-----------------------------------------
		// Force type?
		//-----------------------------------------

		if ( is_array($force) AND count($force) > 0 )
		{
			foreach ( $force as $key => $type )
			{
				$this->xmlrpc->map_type_to_key[ $key ] = $type;
			}
		}
		
		//-----------------------------------------
		// Send...
		//-----------------------------------------
		
		$this->xmlrpc->xml_rpc_return_params( $data );
	}
	
	/*-------------------------------------------------------------------------*/
	// Return API Request( ERROR )
	/*-------------------------------------------------------------------------*/
	/**
	* Return API Request (ERROR)
	*
	* @param	int 	Error Code
	* @param	string  Error message
	* @return	nuffink
	*/
	function api_send_error( $error_code, $error_msg )
	{
		$this->xmlrpc->xml_rpc_return_error( $error_code, $error_msg );
	}
	
	/*-------------------------------------------------------------------------*/
	// Decode API Request
	/*-------------------------------------------------------------------------*/
	/**
	* Decode API Request
	*
	* @param	string	Raw data picked up
	* @return	notsure
	*/
	function api_decode_request( $raw_data )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		//-----------------------------------------
		// Get data...
		//-----------------------------------------
		
		$raw = $this->xmlrpc->xml_rpc_decode( $raw_data );
		
		//-----------------------------------------
		// Process return data
		//-----------------------------------------
		
		$this->api_process_data( $raw );
	}
	
	/*-------------------------------------------------------------------------*/
	// Send API Request
	/*-------------------------------------------------------------------------*/
	/**
	* Send API Request
	*
	* @param	string	URL to send request to
	* @param	string	Method name for API to pick up
	* @param	array	Data to send
	* @param	int  	Complex data: Encode before sending
	* @return	notsure
	*/
	function api_send_request( $url, $method_name, $data=array(), $complex_data=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$return_data             = array();
		$raw                     = array();
		$this->xmlrpc->errors    = array();
		$this->xmlrpc->auth_user = $this->auth_user;
		$this->xmlrpc->auth_pass = $this->auth_pass;
		
		//-----------------------------------------
		// Cookies?
		//-----------------------------------------
		
		if ( is_array( $this->cookies ) AND count( $this->cookies ) )
		{
			$data[ $this->cookie_serialized_key ] = $this->encode_base64_array( $this->cookies );
			$this->xmlrpc->map_type_to_key[ $this->cookie_serialized_key ] = 'base64';
		}
		
		//-----------------------------------------
		// Complex data?
		//-----------------------------------------
		
		if ( $complex_data )
		{
			$_tmp = $data;
			$data = array();
			$data[ $this->serialized_key ] = $this->encode_base64_array( $_tmp );
			$this->xmlrpc->map_type_to_key[ $this->serialized_key ] = 'base64';
		}
		
		//-----------------------------------------
		// Get data...
		//-----------------------------------------
		
		$return_data = $this->xmlrpc->xml_rpc_send( $url, $method_name, $data );
		
		if ( count( $this->xmlrpc->errors ) )
		{
			$this->errors = $this->xmlrpc->errors;
			return;
		}
		
		//-----------------------------------------
		// Process return data
		//-----------------------------------------
	
		$this->api_process_data( $return_data );
	}
	
	/*-------------------------------------------------------------------------*/
	// Process returned data
	/*-------------------------------------------------------------------------*/
	/**
	* Process returned data
	*
	* @param	array	Raw array
	* @return	array   Cleaned array
	*/
	function api_process_data( $raw=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$_params              = $this->xmlrpc->xml_rpc_get_params( $raw );
		$this->method_name    = $this->xmlrpc->xml_rpc_get_method_name( $raw );
		$this->params         = array();
		$this->_raw_in_data   = var_export( $raw, TRUE );
		$this->_raw_out_data  = var_export( $_params, TRUE );
		
		//-----------------------------------------
		// Debug?
		//-----------------------------------------
		
		if ( IPS_XML_RPC_DEBUG_ON )
		{
			$this->xmlrpc->_add_debug( "API_PROCESS_DECODE: IN PARAMS:  " . var_export( $raw, TRUE ) );
			$this->xmlrpc->_add_debug( "API_PROCESS_DECODE: OUT PARAMS: " . var_export( $_params, TRUE ) );
		}
		
		//-----------------------------------------
		// Fix up params
		//-----------------------------------------
		
		if ( isset($_params[0]) AND is_array( $_params[0] ) )
		{
			foreach( $_params[0] as $k => $v )
			{
				if ( $k != '' && $k == $this->serialized_key )
				{
					$_tmp = $this->decode_base64_array( $v );
					
					if ( is_array( $_tmp ) and count( $_tmp ) )
					{
						$this->params = array_merge( $this->params, $_tmp );
					}
				}
				else if ( $k != '' && $k == $this->cookie_serialized_key )
				{
					$_cookies = $this->decode_base64_array( $v );
					
					if ( is_array( $_cookies ) and count( $_cookies ) )
					{
						foreach( $_cookies as $cookie_data )
						{
							if ( $cookie_data['sticky'] == 1 )
					        {
					        	$cookie_data['expires'] = time() + 60*60*24*365;
					        }
							
							$cookie_data['path'] = $cookie_data['path'] ? $cookie_data['path'] : '/';
							
					        @setcookie( $cookie_data['name'], $cookie_data['value'], $cookie_data['expires'], $cookie_data['path'], $cookie_data['domain'] );
					
							if ( IPS_XML_RPC_DEBUG_ON )
							{
								$this->xmlrpc->_add_debug( "API_PROCESS_DECODE: SETTING COOKIE:  " . var_export( $cookie_data, TRUE ) );
							}
						}
					}
				}
				else
				{
					$this->params[ $k ] = $v;
				}
			}
		}
		else if ( is_array( $_params ) )
		{
			$i = 0;
			foreach( $_params as $v )
			{
				$this->params['param'.$i] = $v;
				$i++;
			}
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Encode array
	/*-------------------------------------------------------------------------*/
	/**
	* Encode array
	*
	* @param	array	Raw array
	* @return	string  Encoded string
	*/
	function encode_base64_array( $array )
	{
		return base64_encode( serialize( $array ) );
	}
	
	/*-------------------------------------------------------------------------*/
	// Dencode array
	/*-------------------------------------------------------------------------*/
	/**
	* Dencode array
	*
	* @param	string  Encoded string
	* @return	array	Raw array
	*/
	function decode_base64_array( $data )
	{
		if ( ! is_array( $data ) )
		{
			return unserialize( base64_decode( $data ) );
		}
		else
		{
			return $data;
		}
	}
	
	
	
	
}

?>