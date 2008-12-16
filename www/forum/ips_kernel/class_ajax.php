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
|   > $Date: 2006-08-01 17:02:55 +0100 (Tue, 01 Aug 2006) $
|   > $Revision: 425 $
|   > $Author: matt $
+---------------------------------------------------------------------------
|
|   > AJAX Functions
|   > Module written by Matt Mecham
|   > Date started: Wednesday 2nd August 2006
|
+--------------------------------------------------------------------------
*/

class class_ajax
{
	/**
	* Global IPS class
	*/
	var $ipsclass;
	
	/**
	* XML output
	*/
    var $xml_output;

	/**
	* XML Header
	*/
    var $xml_header;
    
    /**
	* Character sets
	*/
    var $decode_charsets = array(   'iso-8859-1'    => 'ISO-8859-1',
    								'iso8859-1' 	=> 'ISO-8859-1',
    								'iso-8859-15' 	=> 'ISO-8859-15',
    								'iso8859-15' 	=> 'ISO-8859-15',
    								'utf-8'			=> 'UTF-8',
    								'cp866'			=> 'cp866',
    								'ibm866'		=> 'cp866',
    								'cp1251'		=> 'windows-1251',
    								'windows-1251'	=> 'windows-1251',
    								'win-1251'		=> 'windows-1251',
    								'cp1252'		=> 'windows-1252',
    								'windows-1252'	=> 'windows-1252',
    								'koi8-r'		=> 'KOI8-R',
    								'koi8-ru'		=> 'KOI8-R',
    								'koi8r'			=> 'KOI8-R',
    								'big5'			=> 'BIG5',
    								'gb2312'		=> 'GB2312',
    								'big5-hkscs'	=> 'BIG5-HKSCS',
    								'shift_jis'		=> 'Shift_JIS',
    								'sjis'			=> 'Shift_JIS',
    								'euc-jp'		=> 'EUC-JP',
    								'eucjp'			=> 'EUC-JP' );
    								
        
    /*-------------------------------------------------------------------------*/
    // Initialize the class
    /*-------------------------------------------------------------------------*/
    /**
	* INIT the class
	*/
    function class_init()
    {
	    $this->xml_header = "<?xml version=\"1.0\" encoding=\"{$this->ipsclass->vars['gb_char_set']}\"?".'>';
    }

	/*-------------------------------------------------------------------------*/
 	// Convert and make safe an incoming string
 	/*-------------------------------------------------------------------------*/
 	/**
	* Convert and make safe an incoming string
	*/
 	function convert_and_make_safe( $value, $parse_incoming=1 )
 	{
 		$value = rawurldecode( $value );

   		$value = $this->convert_unicode( $value );
   		
   		// This is apparently not needed with the convert_unicode changes I made
   		
		$value = $this->convert_html_entities( $value );
		
		if( $parse_incoming OR ( strtolower($this->ipsclass->vars['gb_char_set']) != 'iso-8859-1' &&
			strtolower($this->ipsclass->vars['gb_char_set']) != 'utf-8' ) )
		{
			$value = $this->ipsclass->parse_clean_value( $value );
		}
		
		return $value;
 	}
 	
 	/*-------------------------------------------------------------------------*/
 	// make string XML safe
 	/*-------------------------------------------------------------------------*/
 	/**
	* Make a string safe for XML transport
	*/
 	function _make_safe_for_xml( $t )
 	{
 		return str_replace( '&amp;#39;', '&#39;', htmlspecialchars( $t ) );
 	}
			
	/*-------------------------------------------------------------------------*/
    // Error handler
    /*-------------------------------------------------------------------------*/
    /**
	* Print an error
	*/
    function error_handler()
    {
    	@header( "Content-type: text/xml" );
    	$this->print_nocache_headers();
    	$this->xml_output = $this->xml_header."\r\n<errors>\r\n";
    	$this->xml_output .= "<error><message>You must be logged in to access this feature</message></error>\r\n";
    	$this->xml_output .= "</errors>";
		print $this->xml_output;
		exit();
    }
    
    /*-------------------------------------------------------------------------*/
    // Return NOTHING :o
    /*-------------------------------------------------------------------------*/
    /**
	* Return a NULL result
	*/
    function return_null($val=0)
    {
    	@header( "Content-type: text/xml" );
    	$this->print_nocache_headers();
    	print $this->xml_header."\r\n<null>{$val}</null>";
    	exit();
    }
    
    /*-------------------------------------------------------------------------*/
    // Return string
    /*-------------------------------------------------------------------------*/
    /**
	* Return a string
	*/
    function return_string($string)
    {
    	@header( "Content-type: text/plain;charset={$this->ipsclass->vars['gb_char_set']}" );
    	$this->print_nocache_headers();
    	print $string;
    	exit();
    }

	/*-------------------------------------------------------------------------*/
    // Return HTML
    /*-------------------------------------------------------------------------*/
    /**
	* Return a HTML
	*/
    function return_html($string)
    {
		//-----------------------------------------
		// Stop one from removing cookie protection
		//-----------------------------------------
		
		$string = ( $string ) ? $string : '<!--nothing-->';
		$string = preg_replace( "#htmldocument\.prototype#is", "HTMLDocument_prototype", $string );
		
		// Fix IE bugs
		$string = str_replace( "&sect", 	"&amp;sect", 	$string );
		
		if ( strtolower( $this->ipsclass->vars['gb_char_set'] ) == 'iso-8859-1' )
		{
			$string = str_replace( "ì", "&#8220;", $string );
			$string = str_replace( "î", "&#8221;", $string );
		}
		
		// Other stuff
		$string = str_replace( "<#IMG_DIR#>", $this->ipsclass->skin['_imagedir'], $string );
		$string = str_replace( "<#EMO_DIR#>", $this->ipsclass->skin['_emodir']  , $string );
		
    	@header( "Content-type: text/html;charset=".$this->ipsclass->vars['gb_char_set'] );
    	$this->print_nocache_headers();
    	print $string;
    	exit();
    }
    
    /*-------------------------------------------------------------------------*/
    // Print no cache headers
    /*-------------------------------------------------------------------------*/
    /**
	* Print nocache headers
	*/
    function print_nocache_headers()
    {
    	header("HTTP/1.0 200 OK");
		header("HTTP/1.1 200 OK");
    	header("Cache-Control: no-cache, must-revalidate, max-age=0");
		header("Expires: 0");
		header("Pragma: no-cache");
	}
	
	/*-------------------------------------------------------------------------*/
    // Convert decimal character references to UTF-8
    /*-------------------------------------------------------------------------*/
    /**
	* Convert a decimal character to UTF-8
	*/
	function dec_char_ref_to_utf8($int=0)
	{
		$return = '';

		if ( $int < 0 )
		{
			return chr(0);
		}
		else if ( $int <= 0x007f )
		{
			$return .= chr($int);
		}
		else if ( $int <= 0x07ff )
		{
			$return .= chr(0xc0 | ($int >> 6));
			$return .= chr(0x80 | ($int & 0x003f));
		}
		else if ( $int <= 0xffff )
		{
			$return .= chr(0xe0 | ($int  >> 12));
			$return .= chr(0x80 | (($int >> 6) & 0x003f));
			$return .= chr(0x80 | ($int  & 0x003f));
		}
		else if ( $int <= 0x10ffff )
		{
			$return .= chr(0xf0 | ($int  >> 18));
			$return .= chr(0x80 | (($int >> 12) & 0x3f));
			$return .= chr(0x80 | (($int >> 6) & 0x3f));
			$return .= chr(0x80 | ($int  &  0x3f));
		}
		else
		{ 
			return chr(0);
		}
		
		return $return;
	}

	/*-------------------------------------------------------------------------*/
    // Helper function
    /*-------------------------------------------------------------------------*/
	
	function dec_char_ref_to_utf8_hexdec( $matches )
	{
		return $this->dec_char_ref_to_utf8( hexdec( $matches[1] ) );
	}
	
	/*-------------------------------------------------------------------------*/
	// Convert Ajax unicode
	/*-------------------------------------------------------------------------*/
	
	function convert_unicode($t)
	{
		if ( strtolower($this->ipsclass->vars['gb_char_set']) == 'utf-8' )
		{
			return preg_replace_callback( '#%u([0-9A-F]{1,4})#i', array( &$this, 'dec_char_ref_to_utf8_hexdec' ), utf8_encode($t) );
		}
		else
		{
			return preg_replace_callback( '#%u([0-9A-F]{1,4})#i', create_function( '$matches', "return '&#' . hexdec(\$matches[1]) . ';';" ), $t );
		}
		
		// Javascript escape function always sends unicode
		
		$text = preg_replace_callback( '#%u([0-9A-F]{1,4})#i', array( &$this, 'dec_char_ref_to_utf8_hexdec' ), utf8_encode($t) );
	
		if ( strtolower($this->ipsclass->vars['gb_char_set']) != 'utf-8' )
		{
			$text = $this->ipsclass->txt_convert_charsets( $text, 'UTF-8' );
		}
		
		return $text ? $text : $t;
	}
	
	/**
	* Convert HTML entities and respect character sets
	*/
	function convert_html_entities($t)
	{
		//-----------------------------------------
		// Try and fix up HTML entities with missing ;
		//-----------------------------------------
		
		$t = preg_replace( "/&#(\d+?)([^\d;])/i", "&#\\1;\\2", $t );

		//-----------------------------------------
		// Continue...
		//-----------------------------------------

		if ( strtolower($this->ipsclass->vars['gb_char_set']) != 'iso-8859-1' &&
			strtolower($this->ipsclass->vars['gb_char_set']) != 'utf-8' )
   		{
	   		if ( array_key_exists( strtolower($this->ipsclass->vars['gb_char_set']), $this->decode_charsets ) )
	   		{
		   		$this->ipsclass->vars['gb_char_set'] = $this->decode_charsets[strtolower($this->ipsclass->vars['gb_char_set'])];

		   		$t = html_entity_decode( $t, ENT_NOQUOTES, $this->ipsclass->vars['gb_char_set'] );
	   		}
	   		else
	   		{
		   		// Take a crack at entities in other character sets
		   		
		   		$t = str_replace( "&amp;#", "&#", $t );
		   		
		   		// If mb functions available, we can knock out html entities for a few more char sets

				if( function_exists('mb_list_encodings') )
				{
					// PHP 5, preferred method
					
					$valid_encodings = array();
					$valid_encodings = mb_list_encodings();
					
					if( count($valid_encodings) )
					{
						if( in_array( strtoupper($this->ipsclass->vars['gb_char_set']), $valid_encodings ) )
						{
							$t = mb_convert_encoding( $t, strtoupper($this->ipsclass->vars['gb_char_set']), 'HTML-ENTITIES' );
						}
					}
				}
				else if( function_exists('mb_convert_encoding') )
				{
					// PHP 4 support
					
					// Though this is quite tedious, let's check to see if encoding is supported....
					// http://us2.php.net/manual/en/ref.mbstring.php
					
					$valid_encodings = array( 'UCS-4', 'UCS-4BE', 'UCS-4LE', 'UCS-2', 'UCS-2BE', 'UCS-2LE', 'UTF-32', 'UTF-32BE',
												'UTF-32LE', 'UTF-16', 'UTF-16BE', 'UTF-16LE', 'UTF-7', 'UTF7-IMAP', 'UTF-8',
												'ASCII', 'EUC-JP', 'SJIS', 'EUCJP-WIN', 'SJIS-WIN', 'ISO-2022-JP', 'JIS', 'ISO-8859-1',
												'ISO-8859-2', 'ISO-8859-3', 'ISO-8859-4', 'ISO-8859-5', 'ISO-8859-6', 'ISO-8859-7',
												'ISO-8859-8', 'ISO-8859-9', 'ISO-8859-10', 'ISO-8859-13', 'ISO-8859-14', 'ISO-8859-15',
												'EUC-CN', 'CP936', 'EUC-TW', 'HZ', 'CP950', 'BIG-5', 'EUC-KR', 'CP949', 'ISO-2022-KR',
												'WINDOWS-1251', 'CP1251', 'WINDOWS-1252', 'CP1252', 'CP866', 'KOI8-R' );
		
					if( in_array( strtoupper($this->ipsclass->vars['gb_char_set']), $valid_encodings ) )
					{
						$t = mb_convert_encoding( $t, strtoupper($this->ipsclass->vars['gb_char_set']), 'HTML-ENTITIES' );
					}
				}
	   		}
   		}
   		
   		return $t;
	}

        
}

?>