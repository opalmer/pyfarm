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
|   > XML-RPC handling methods (KERNEL)
|   > Module written by Matt Mecham
|   > Date started: 6th January 2006
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/

/**
* IPS Kernel Pages: XML_RPC Creation and Extraction Functions
*
*
* Example Usage:
* <code>
* SENDING XML-RPC Request
* (Optional)
* $xmlrpc->map_type_to_key['key']  = 'string';
* $xmlrpc->map_type_to_key['key2'] = 'base64';
* $return = $xmlrpc->xml_rpc_send( 'http://domain.com/xml-rpc_server.php', 'methodNameHere', array( 'key' => 'value', 'key2' => 'value2' ) );
* if ( $xmlrpc->errors )
* {
* 	print_r( $xmlrpc->errors );
* }
* 
* Decoding XML-RPC
* $xmlrpc->decode( $raw_xmlrpc_text );
* 
* print_r( $xmlrpc->xmlrpc_params );
* RETURN
* $xmlrpc->xml_rpc_return_true();
* </code>
*
* @package		IPS_KERNEL
* @subpackage	XMLHandling
* @author		Matt Mecham
* @copyright	Invision Power Services, Inc.
* @version		2.1
*/

/**
* XML Creation and Extraction Functions
*
* Methods and functions for handling XML documents
*
* @package		IPS_KERNEL
* @subpackage	XMLHandling
* @author  		Matt Mecham
* @version		2.1
*/
class class_xml_rpc
{
	/**
	* XML header
	*
	* @var string
	*/
	var $header   = "";
	
	/**
	* DOC type
	*
	* @var string
	*/
	var $doc_type = 'UTF-8';
	
	/**
	* Error array
	*
	* @var array
	*/
	var $errors = array();
	
	/**
	* Var types
	*/
	var $var_types  = array('string', 'int', 'i4', 'double', 'dateTime.iso8601', 'base64', 'boolean');
	
	/**
	* Extracted xmlrpc params
	* @var array
	*/
	var $xmlrpc_params = array();
	
	/**
	* Optionally map types to key
	*/
	var $map_type_to_key = array();
	
	/**
	* Auth required
	*/
	var $auth_user = '';
	var $auth_pass = '';
	
	/*-------------------------------------------------------------------------*/
	// CONSTRUCTOR
	/*-------------------------------------------------------------------------*/
	
	function class_xml_rpc()
	{
		$this->header = '<?xml version="1.0" encoding="'.$this->doc_type.'"?'.'>';
	}
	
	/*-------------------------------------------------------------------------*/
	// Decode an XML RPC document 
	/*-------------------------------------------------------------------------*/
	/**
	* Decode an XML RPC document
	*
	* @param	string	XML-RPC data
	* @param	array   Array of fields to send (must be in key => value pairings)
	* @return	void
	*/
	function xml_rpc_decode( $_xml )
	{
		$xml_parser =& new xmlrpc_parser();
	    $data       = $xml_parser->parse( $_xml );
	    $xml_parser->destruct();
	
		if ( isset( $data['methodResponse']['fault'] ) )
		{
		    $tmp            = $this->xml_rpc_adjust_value( $data['methodResponse']['fault']['value'] );
			$this->errors[] = $tmp['faultString'];
		}
		
		$this->xmlrpc_params      = $this->xml_rpc_get_params( $data );
		$this->xmlrpc_method_call = $this->xml_rpc_get_method_name( $data );
		
		//-----------------------------------------
		// Debug?
		//-----------------------------------------
		
		if ( IPS_XML_RPC_DEBUG_ON )
		{
			$this->_add_debug( "DECODING XML data: " . $_xml );
			$this->_add_debug( "DECODE RESULT XML data: " . var_export( $data, TRUE ) );
		}

		return $data;
	}
	
	/**
	* Adjust value of param
	*/
	function & xml_rpc_adjust_value( & $current_node )
	{
	    if ( is_array( $current_node ) )
		{
	        if ( isset($current_node['array']) )
			{
	            if ( ! is_array($current_node['array']['data']) )
				{
	                $temp = array();
	            }
				else
				{
	                $temp = &$current_node['array']['data']['value'];
	
	                if ( is_array($temp) and array_key_exists(0, $temp) )
					{
	                    $count = count($temp);
	
	                    for( $n = 0 ; $n < $count ; $n++ )
						{
	                        $temp2[$n] = & $this->xml_rpc_adjust_value($temp[$n]);
	                    }
	
	                    $temp = &$temp2;
	
	                }
					else
					{
	                    $temp2 = & $this->xml_rpc_adjust_value($temp);
	                    $temp = array(&$temp2);
	                }
	            }
	        }
			elseif ( isset($current_node['struct']) )
			{
	            if ( ! is_array($current_node['struct']) )
				{
	                return array();
	            }
				else
				{
	                $temp = &$current_node['struct']['member'];
	
	                if ( is_array($temp) and array_key_exists(0, $temp) )
					{
	                    $count = count($temp);
	
	                    for( $n = 0 ; $n < $count ; $n++ )
						{
	                        $temp2[$temp[$n]['name']] = & $this->xml_rpc_adjust_value($temp[$n]['value']);
	                    }
	                }
					else
					{
	                    $temp2[$temp['name']] = & $this->xml_rpc_adjust_value($temp['value']);
	                }
	                $temp = &$temp2;
	            }
	        }
			else
			{
	            $got_it = false;
	
	            foreach( $this->var_types as $type )
				{
	                if ( array_key_exists($type, $current_node) )
					{
	                    $temp   = &$current_node[$type];
	                    $got_it = true;
	                    break;
	                }
	            }
	
	            if ( ! $got_it )
				{
	                $type = 'string';
	                
	            }
	
	            switch ($type)
				{
	                case 'int':
	 				case 'i4':
					case 'integer':
					case 'integar':
						$temp = (int)    $temp;
						break;
	                case 'string':
						$temp = (string) $temp;
						break;
	                case 'double':
						$temp = (double) $temp; 
						break;
	                case 'boolean':
						$temp = (bool)   $temp;
						break;
					case 'base64':
						$temp = trim($temp);
						break;
	            }
	        }
	    }
		else
		{
	        $temp = (string) $current_node;
	    }
	
	    return $temp;
	}
	
	/**
	* Get the params from an XML-RPC return
	*/
	function xml_rpc_get_params( $request )
	{
		if ( isset( $request['methodCall']['params'] ) AND is_array( $request['methodCall']['params'] ) )
		{
			$temp = & $request['methodCall']['params']['param'];
		}
		else if ( isset( $request['methodResponse']['params'] ) AND is_array( $request['methodResponse']['params'] ) )
		{
			$temp = & $request['methodResponse']['params']['param'];
		}
		else
		{
			return array();
		}
	   
	    if ( is_array( $temp ) and array_key_exists( 0, $temp ) )
		{
            $count = count($temp);

            for( $n = 0 ; $n < $count ; $n++)
			{
                $temp2[$n] = & $this->xml_rpc_adjust_value($temp[$n]['value']);
            }
        }
		else
		{
            $temp2[0] = & $this->xml_rpc_adjust_value($temp['value']);
        }

        $temp = &$temp2;

        return $temp;
	}
	
	/**
	* Returns the method name
	*/
	function xml_rpc_get_method_name( $request )
	{
	    return isset( $request['methodCall']['methodName'] ) ? $request['methodCall']['methodName'] : '';
	}
	
	/*-------------------------------------------------------------------------*/
	// Create and send an XML document xml_rpc_decode
	/*-------------------------------------------------------------------------*/
	/**
	* Create and send an XML document
	*
	* @param	string	URL to send XML-RPC data to
	* @param	array   Array of fields to send (must be in key => value pairings)
	* @return	void
	*/
	function xml_rpc_send( $url, $method_name='', $data_array=array() )
	{
		//-----------------------------------------
		// Build RPC request
		//-----------------------------------------
		
		$xmldata = $this->xml_rpc_build_document( $data_array, $method_name );
		
		if ( $xmldata )
		{
			//-----------------------------------------
			// Debug?
			//-----------------------------------------
			
			if ( IPS_XML_RPC_DEBUG_ON )
			{
				$this->_add_debug( "SENDING XML data: " . $xmldata );
			}
			
			//-----------------------------------------
			// Continue
			//-----------------------------------------
			
			return $this->xml_rpc_post( $url, $xmldata );
		}
		else
		{
			return FALSE;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Return single value
	/*-------------------------------------------------------------------------*/
	/**
	* Prints a true document and exits
	*
	* @return    void
	*/
	function xml_rpc_return_value( $value )
	{
	    $to_print = $this->header."<methodResponse>
	       <params>
	          <param>
	             <value>{$value}</value>
	             </param>
	          </params>
	       </methodResponse>";

	    @header( "Connection: close" );
	    @header( "Content-length: ".strlen($to_print) );
	    @header( "Content-type: text/xml" );
	    @header( "Date: " . date("r") );
	    print $to_print;

	    exit();
	}
	
	/*-------------------------------------------------------------------------*/
	// Creates an XML-RPC complex document
	/*-------------------------------------------------------------------------*/
	/**
	* Creates an XML-RPC complex document
	*
	* @param	array   Array of fields to send (must be in key => value pairings)
	* @param	string	Method name (optional)
	* @return	string	finished document
	*/
	function xml_rpc_build_document( $data_array, $method_name='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$xmldata  = "";
		$root_tag = 'methodCall';
		
		//-----------------------------------------
		// Test
		//-----------------------------------------
		
		if ( ! is_array( $data_array ) or ! count( $data_array ) )
		{
			return FALSE;
		}
		
		if ( ! $method_name )
		{
			$root_tag = 'methodResponse';
		}
		
		$xmldata  = $this->header . "\n";
		$xmldata .= "<".$root_tag.">\n";
		
		if ( $method_name )
		{
			$xmldata .= "\t<methodName>".$method_name."</methodName>\n";
		}
		
		$xmldata .= "\t<params>\n";
		$xmldata .= "\t\t<param>\n";
		$xmldata .= "\t\t\t<value>\n";

		if ( isset( $data_array[0] ) AND is_array( $data_array[0] ) )
		{
 			$xmldata .= "\t\t\t<array>\n";
			$xmldata .= "\t\t\t\t<data>\n";

			foreach( $data_array as $k => $v )
			{
				$xmldata .= "\t\t\t\t\t<value>\n";
				$xmldata .= "\t\t\t\t\t\t<struct>\n";
				
				foreach( $v as $k2 => $v2 )
				{
					$_type = $this->map_type_to_key[ $k2 ] ? $this->map_type_to_key[ $k2 ] : $this->get_xmlrpc_string_type( $v2 );

					$xmldata .= "\t\t\t\t\t\t\t<member>\n";
					$xmldata .= "\t\t\t\t\t\t\t\t<name>".$k2."</name>\n";
					$xmldata .= "\t\t\t\t\t\t\t\t<value><".$_type.">" . htmlspecialchars($v2) . "</".$_type."></value>\n";
					$xmldata .= "\t\t\t\t\t\t\t</member>\n";
				}
				
				$xmldata .= "\t\t\t\t\t\t</struct>\n";
				$xmldata .= "\t\t\t\t\t</value>\n";
            }

			$xmldata .= "\t\t\t\t</data>\n";
			$xmldata .= "\t\t\t</array>\n";
		}
		else
		{
			$xmldata .= "\t\t\t<struct>\n";

			foreach( $data_array as $k => $v )
			{
				if ( is_array( $v ) )
				{
					$xmldata .= $this->_xml_rpc_build_document_recurse( "", $k, $v, 4 );
				}
				else
				{
					$_type = isset( $this->map_type_to_key[ $k ] ) ? $this->map_type_to_key[ $k ] : $this->get_xmlrpc_string_type( $v );

					$xmldata .= "\t\t\t\t<member>\n";
					$xmldata .= "\t\t\t\t\t<name>".$k."</name>\n";
					$xmldata .= "\t\t\t\t\t<value><".$_type.">" . htmlspecialchars($v) . "</".$_type."></value>\n";
					$xmldata .= "\t\t\t\t</member>\n";
				}
			}

			$xmldata .= "\t\t\t</struct>\n";
		}

		$xmldata .= "\t\t\t</value>\n";
		$xmldata .= "\t\t</param>\n";
		$xmldata .= "\t</params>\n";
		$xmldata .= "</".$root_tag.">";
		
		return $xmldata;
	}
	
	/*-------------------------------------------------------------------------*/
	// Creates an XML-RPC complex document
	/*-------------------------------------------------------------------------*/
	/**
	* Creates an XML-RPC complex document
	*
	* @param	array   Array of fields to send (must be in key => value pairings)
	* @param	string	Method name (optional)
	* @return	string	finished document
	*/
	function _xml_rpc_build_document_recurse( $xmldata, $k, $v, $depth=4 )
	{
		$xmldata .= "\t<member>\n";
		$xmldata .= "\t\t<name>".$k."</name>\n";
		$xmldata .= "\t\t<value>\n";
		$xmldata .= "\t\t\t<array>\n";
		$xmldata .= "\t\t\t\t<data>\n";
		$xmldata .= "\t\t\t\t\t<value>\n";
		$xmldata .= "\t\t\t\t\t\t<struct>\n";
		
		foreach( $v as $_k => $_v )
		{
			if ( is_array( $_v ) )
			{
				$depth++;
				$xmldata .= $this->_xml_rpc_build_document_recurse( $xmldata, $k, $v, $depth );
			}
			else
			{
				$_type = isset( $this->map_type_to_key[ $_k ] ) ? $this->map_type_to_key[ $_k ] : $this->get_xmlrpc_string_type( $_v );
				
				$xmldata .= "\t\t\t\t\t\t\t<member>\n";
				$xmldata .= "\t\t\t\t\t\t\t\t<name>".$_k."</name>\n";
				$xmldata .= "\t\t\t\t\t\t\t\t<value><".$_type.">" . htmlspecialchars($_v) . "</".$_type."></value>\n";
				$xmldata .= "\t\t\t\t\t\t\t</member>\n";
			}
		}
		
		$xmldata .= "\t\t\t\t\t\t</struct>\n";
		$xmldata .= "\t\t\t\t\t</value>\n";
		$xmldata .= "\t\t\t\t</data>\n";
		$xmldata .= "\t\t\t</array>\n";
		$xmldata .= "\t\t</value>\n";
		$xmldata .= "\t</member>\n";
		
		return $xmldata;
	}
	
	/*-------------------------------------------------------------------------*/
	// Return True
	/*-------------------------------------------------------------------------*/
	/**
	* Prints a true document and exits
	*
	* @return	void
	*/
	function xml_rpc_return_true()
	{
		$to_print = $this->header."
		<methodResponse>
		   <params>
		      <param>
		         <value><boolean>1</boolean></value>
		         </param>
		      </params>
		   </methodResponse>";
		
		@header( "Connection: close" );
		@header( "Content-length: ".strlen($to_print) );
		@header( "Content-type: text/xml" );
		@header( "Date: " . date("r") ); 
		print $to_print;
		
		exit();
	}
	
	/*-------------------------------------------------------------------------*/
	// Return Params
	/*-------------------------------------------------------------------------*/
	/**
	* Prints a document and exits
	*
	* @param	array  Array of params to return in key => value pairs
	* @return	void
	*/
	function xml_rpc_return_params( $data_array )
	{
		$to_print = $this->xml_rpc_build_document( $data_array );
		@header( "Connection: close" );
		@header( "Content-length: ".strlen($to_print) );
		@header( "Content-type: text/xml" );
		@header( "Date: " . date("r") );
		@header( "Pragma: no-cache" );
		@header( "Cache-Control: no-cache" );
		print $to_print;
		
		exit();
	}
	
	/*-------------------------------------------------------------------------*/
	// Return Error
	/*-------------------------------------------------------------------------*/
	/**
	* Prints a true document and exits
	*
	* @param	int		Error code
	* @param	string	Error Message
	* @return	void
	*/
	function xml_rpc_return_error( $error_code, $error_msg )
	{
		$to_print = $this->header."
		<methodResponse>
		   <fault>
		      <value>
		         <struct>
		            <member>
		               <name>faultCode</name>
		               <value>
		                  <int>".intval($error_code)."</int>
		                  </value>
		               </member>
		            <member>
		               <name>faultString</name>
		               <value>
		                  <string>".$error_msg."</string>
		                  </value>
		               </member>
		            </struct>
		         </value>
		            </fault>
		   </methodResponse>";
		
		@header( "Connection: close" );
		@header( "Content-length: ".strlen($to_print) );
		@header( "Content-type: text/xml" );
		@header( "Date: " . date("r") ); 
		print $to_print;
		
		exit();
	}
	
	
	/*-------------------------------------------------------------------------*/
	// Post the document
	/*-------------------------------------------------------------------------*/
	/**
	* Create and send an XML document
	*
	* @param	string	URL to send XML-RPC data to
	* @param	array   XML-RPC data
	* @return	void
	*/
	function xml_rpc_post( $file_location, $xmldata='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$data            = null;
		$fsocket_timeout = 10;
		$header          = "";
		
		//-----------------------------------------
		// Send it..
		//-----------------------------------------

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
      	
		//-----------------------------------------
		// User and pass?
		//-----------------------------------------
		
		if ( ! $this->auth_user AND $url_parts['user'] )
		{
			$this->auth_user = $url_parts['user'];
			$this->auth_pass = $url_parts['pass'];
		}
		
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
		
		if ( ! $fp = @fsockopen( $host, $port, $errno, $errstr, $fsocket_timeout ) )
	    {
			$this->errors[] = "CONNECTION REFUSED FROM $host";
			return FALSE;
        
		}
		else
		{
			$header  = "POST $path HTTP/1.0\r\n";
			$header .= "Host: $host\r\n";
			
			if ( $this->auth_user && $this->auth_pass )
			{
				$this->_add_debug( "Authorization: Basic Performed" );

				$header .= "Authorization: Basic ".base64_encode("{$this->auth_user}:{$this->auth_pass}")."\r\n";
			}
			
			$header .= "Connection: close\r\n";
			$header .= "Content-Type: text/xml\r\n";
			$header .= "Content-Length: " . strlen($xmldata) . "\r\n\r\n";
			
			if ( ! fputs( $fp, $header . $xmldata ) )
			{
				$this->errors[] = "Unable to send request to $host!";
				return FALSE;
			}
         }

         @stream_set_timeout($fp, $fsocket_timeout);
        
         $status = @socket_get_status($fp);
        
         while( ! feof($fp) && ! $status['timed_out'] )         
         {
            $data  .= fgets ( $fp, 8192 );
            $status = socket_get_status($fp);
         }
        
        fclose ($fp);
       
        //-------------------------------
        // Strip headers
        //-------------------------------
        
        $tmp  = split("\r\n\r\n", $data, 2);
        $data = $tmp[1];

		//-----------------------------------------
		// Debug?
		//-----------------------------------------
		
		if ( IPS_XML_RPC_DEBUG_ON )
		{
			$this->_add_debug( "POST RESPONSE to $host{$path}: " . $data );
		}
		
		//-----------------------------------------
		// Continue
		//-----------------------------------------
		
		return $this->xml_rpc_decode( $data );
	}
	
	/*-------------------------------------------------------------------------*/
	// Get the XML-RPC string type
	/*-------------------------------------------------------------------------*/
	/**
	* Get the XML-RPC string type
	*
	* @param	string	String
	* @return	string	XML-RPC String Type
	*/
	function get_xmlrpc_string_type( $string )
	{
		$type = gettype( $string );
		
		switch( $type )
		{
			default:
			case 'string':
				$type = 'string';
				break;
			case 'integer':
				$type = 'int';
				break;
			case 'double':
				$type = 'double';
				break;
			case 'null':
			case 'boolean':
				$type = 'boolean';
				break;
		}
		
		return $type;
	}
	
	/**
	* Add debug message
	*/
	function _add_debug( $msg )
	{
		if ( IPS_XML_RPC_DEBUG_FILE AND IPS_XML_RPC_DEBUG_ON )
		{
			$full_msg = "==================================================================\n"
					   . "SCRIPT NAME: " . $_SERVER["SCRIPT_NAME"] . "\n"
					   . gmdate( 'r' ) . ' - ' . $_SERVER['REMOTE_ADDR'] . ' - ' . $msg . "\n"
					   . "==================================================================\n";
			
			if ( $FH = @fopen( IPS_XML_RPC_DEBUG_FILE, 'a+' ) )
			{
				fwrite( $FH, $full_msg, strlen( $full_msg ) );
				fclose( $FH );
			}
			
			return TRUE;
		}
	}
}

class xmlrpc_parser
{
	/**
	* Parser object
	*/
    var $parser;
	/**
	* Current document
	*/
    var $document;
	/**
	* Current tag
	*/
    var $current;
	/**
	* Parent tag
	*/
    var $parent;
	/**
	* Parents
	*/
    var $parents;
	/**
	* Last opened tag
	*/
    var $last_opened_tag;
	
	/**
	* Constructor
	*/
    function xmlrpc_parser( $data=null )
	{
        $this->parser = xml_parser_create();

        xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
        xml_set_object(               $this->parser, $this);
        xml_set_element_handler(      $this->parser, "rpc_open", "rpc_close");
        xml_set_character_data_handler($this->parser, "rpc_data");
    }
	
	/**
	* Object destructor
	*/
    function destruct()
	{
        xml_parser_free( $this->parser );
    }
	
	/**
	* Parse the XML data
	*/
    function parse( $data )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
        $this->document        = array();
        $this->parent          = &$this->document;
        $this->parents         = array();
        $this->last_opened_tag = NULL;

		//-----------------------------------------
		// Parse
		//-----------------------------------------
		
        xml_parse($this->parser, $data);
		
		//-----------------------------------------
		// Return...
		//-----------------------------------------
		$tmp = $this->document;
        return $tmp;
    }
	
	/**
	* Open handler for XML object
	*/
    function rpc_open($parser, $tag, $attributes)
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
        $this->data            = "";
        $this->last_opened_tag = $tag;

        if ( array_key_exists( $tag, $this->parent ) )
		{
            if ( is_array( $this->parent[$tag] ) and array_key_exists( 0, $this->parent[$tag] ) )
			{
                $key = is_array( $this->parent[$tag] ) ? count( array_filter( array_keys($this->parent[$tag]), 'is_numeric' ) ) : 0;
            }
			else
			{
                $temp = &$this->parent[$tag];
                unset($this->parent[$tag]);

                $this->parent[$tag][0] = &$temp;

                if ( array_key_exists( $tag ." attr", $this->parent ) )
				{
                    $temp = &$this->parent[ $tag ." attr" ];
                    unset($this->parent[ $tag ." attr" ]);
                    $this->parent[$tag]["0 attr"] = &$temp;
                }

                $key = 1;
            }

            $this->parent = &$this->parent[$tag];
        }
		else
		{
            $key = $tag;
        }

        if ( $attributes )
		{
            $this->parent[ $key ." attr" ] = $attributes;
        }

        $this->parent[$key] = array();
        $this->parent       = &$this->parent[$key];

        //array_unshift($this->parents, &$this->parent);
        $this->array_unshift_ref($this->parents, $this->parent);
    }
    
    function array_unshift_ref(&$array, &$value)
	{
	   $return = array_unshift($array,'');
	   $array[0] =& $value;
	   return $return;
	}    

	/**
	* XML data handler
	*/
    function rpc_data($parser, $data)
	{
        if ( $this->last_opened_tag != NULL )
		{
            $this->data .= $data;
        }
    }
	
	/**
	* XML close handler
	*/
    function rpc_close($parser, $tag)
	{
		if ( $this->last_opened_tag == $tag )
		{
            $this->parent = $this->data;
            $this->last_opened_tag = NULL;
        }

        array_shift($this->parents);

        $this->parent = &$this->parents[0];
    }
}

?>