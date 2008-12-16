<?php
/*
+--------------------------------------------------------------------------
|   Invision Power Services Core Class
|   =============================================
|   by Matthew Mecham, Brandon Farber, Josh Williams, Remco Wilting
|   (c) 2001 - 2006 Invision Power Services, Inc.
|   http://www.invisionpower.com
|   =============================================
+---------------------------------------------------------------------------
|   > $Date: 2006-12-06 15:03:36 +0000 (Wed, 06 Dec 2006) $
|   > $Revision: 774 $
|   > $Author: matt $
+---------------------------------------------------------------------------
|
|   > Core IPS Functions
|   > Module written by Matthew Mecham, Brandon Farber, Josh Williams, Remco Wilting
|   > Date started: 6th December 2006
+--------------------------------------------------------------------------
*/

/**
*
* @package		Kernel
* @author		Matthew Mecham, Brandon Farber, Josh Williams, Remco Wilting
* @copyright	Invision Power Services, Inc.
* @version		NA
*/

if ( ! defined( 'IPSCLASS_LOADED' ) )
{
	/**
	* IPSCLASS loaded Flag
	*/
	define( 'IPSCLASS_LOADED', 1 );
}

/**
* Main ipsclass class.
*
* This class holds all the non-class specific functions
*
* @package	InvisionPowerBoard
* @author   Matthew Mecham, Brandon Farber, Josh Williams
* @version	NA
*/
class class_ipsclass_core
{
	/**
	* _GET _POST Input Array
	*
	* @var array
	*/
	var $input = array();
	
	/**
	* IP address
	* @var string
	*/
	var $ip_address = '';
	
	/**
	* SHOULD BE SET UP BY THE REQUESTING CLASS
	*/
	var $stronghold_cookie_name = 'ipb_stronghold';
	var $allow_unicode          = 1;
	var $sensitive_cookies      = array();
	
	/**
	* Remap array
	* array(  ORIGINAL SETTING NAME  -  APP SETTING NAME )
	* @var array
	*/
	var $remap = array( 'cookie_stronghold' => 'cookie_stronghold',
					    'sql_pass'          => 'sql_pass',
						'sql_user'          => 'sql_user',
						'number_format'		=> 'number_format',
						'cookie_domain'		=> 'cookie_domain',
						'cookie_path'		=> 'cookie_path',
						'cookie_id'			=> 'cookie_id',
						'strip_space_chr'	=> 'strip_space_chr',
						'sql_database'		=> 'sql_database',
						'header_redirect'	=> 'header_redirect',
					  );
	
	/*-------------------------------------------------------------------------*/
	// Initiate IP Address
	/*-------------------------------------------------------------------------*/
	/**
	* Initiates the IP Address
	* @param	int		Match forwarded for flag
	* @return	string	IP Address
	*/
	function _init_ip_address( $match_forwarded_for=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$addrs = array();
		
		//-----------------------------------------
		// Sort out the accessing IP
		//-----------------------------------------
		
		if ( $match_forwarded_for )
		{
			foreach( array_reverse( explode( ',', $this->my_getenv('HTTP_X_FORWARDED_FOR') ) ) as $x_f )
			{
				$x_f = trim($x_f);
				
				if ( preg_match( '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $x_f ) )
				{
					$addrs[] = $x_f;
				}
			}
			
			$addrs[] = $this->my_getenv('HTTP_CLIENT_IP');
			$addrs[] = $this->my_getenv('HTTP_PROXY_USER');
		}
		
		$addrs[] = $this->my_getenv('REMOTE_ADDR');
		
		//-----------------------------------------
		// Do we have one yet?
		//-----------------------------------------
		
		foreach ( $addrs as $ip )
		{
			if ( $ip )
			{
				preg_match( "/^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$/", $ip, $match );

				$this->ip_address = $match[1].'.'.$match[2].'.'.$match[3].'.'.$match[4];
				
				if ( $this->ip_address AND $this->ip_address != '...' )
				{
					break;
				}
			}
		}
		
		//-----------------------------------------
		// Make sure we take a valid IP address
		//-----------------------------------------
		 
		if ( ( ! $this->ip_address OR $this->ip_address == '...' ) AND ! isset( $_SERVER['SHELL'] ) )
		{
			print "Could not determine your IP address";
			exit();
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Initiate the class
	/*-------------------------------------------------------------------------*/
	
	/**
	* Initial ipsclass, set up some variables for later
	* Populates:
	* $this->time_options, $this->num_format, $this->get_magic_quotes, $this->ip_address
	* $this->user_agent, $this->browser, $this->operating_system
	*
	* @return void;
	*/
	function core_initiate_ipsclass()
	{
		//-----------------------------------------
		// Remap...
		//-----------------------------------------
		
		if ( is_array( $this->remap ) AND count( $this->remap ) )
		{
			foreach( $this->remap as $original => $custom )
			{
				if ( $original != $custom )
				{
					$this->vars[ $original ] = $this->vars[ $custom ];
				}
			}
		}
		
		//-----------------------------------------
		// Make a safe query string
		//-----------------------------------------
		
		$this->query_string_safe = str_replace( '&amp;amp;', '&amp;', $this->parse_clean_value( urldecode($this->my_getenv('QUERY_STRING')) ) );
		$this->query_string_real = str_replace( '&amp;'    , '&'    , $this->query_string_safe );
		
		//-----------------------------------------
		// Get user-agent, browser and OS
		//-----------------------------------------
		
		$this->user_agent       = $this->parse_clean_value($this->my_getenv('HTTP_USER_AGENT'));
		$this->browser          = $this->fetch_browser();
		$this->operating_system = $this->fetch_os();
			
		//-----------------------------------------
		// Can we use fancy JS? IE6, Safari, Moz
		// and opera 7.6 
		//-----------------------------------------
		
		if ( $this->browser['browser'] == 'ie' AND $this->browser['version'] >= 6.0 )
		{
			$this->can_use_fancy_js = 1;
		}
		else if ( $this->browser['browser'] == 'gecko' AND $this->browser['version'] >= 20030312 )
		{
			$this->can_use_fancy_js = 1;
		}
		else if ( $this->browser['browser'] == 'mozilla' AND $this->browser['version'] >= 1.3 )
		{
			$this->can_use_fancy_js = 1;
		}
		else if ( $this->browser['browser'] == 'opera' AND $this->browser['version'] >= 7.6 )
		{
			$this->can_use_fancy_js = 1;
		}
		else if ( $this->browser['browser'] == 'safari' AND $this->browser['version'] >= 120)
		{
			$this->can_use_fancy_js = 1;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Get browser
	// Return: unknown, windows, mac
	/*-------------------------------------------------------------------------*/
	
	/**
	* Fetches the user's operating system
	*
	* @return	string
	*/
	
	function fetch_os()
	{
		$useragent = strtolower($this->my_getenv('HTTP_USER_AGENT'));
		
		if ( strstr( $useragent, 'mac' ) )
		{
			return 'mac';
		}
		
		if ( preg_match( '#wi(n|n32|ndows)#', $useragent ) )
		{
			return 'windows';
		}
		
		return 'unknown';
	}
	
	/*-------------------------------------------------------------------------*/
	// Get browser
	// Return: unknown, opera, IE, mozilla, konqueror, safari
	/*-------------------------------------------------------------------------*/
	
	/**
	* Fetches the user's browser from their user-agent
	*
	* @return	array [ browser, version ]
	*/
	
	function fetch_browser()
	{
		$version   = 0;
		$browser   = "unknown";
		$useragent = strtolower($this->my_getenv('HTTP_USER_AGENT'));
		
		//-----------------------------------------
		// Opera...
		//-----------------------------------------
		
		if ( strstr( $useragent, 'opera' ) )
		{
			preg_match( "#opera[ /]([0-9\.]{1,10})#", $useragent, $ver );
			
			return array( 'browser' => 'opera', 'version' => $ver[1] );
		}
		
		//-----------------------------------------
		// IE...
		//-----------------------------------------
		
		if ( strstr( $useragent, 'msie' ) )
		{
			preg_match( "#msie[ /]([0-9\.]{1,10})#", $useragent, $ver );
			
			return array( 'browser' => 'ie', 'version' => $ver[1] );
		}
		
		//-----------------------------------------
		// Safari...
		//-----------------------------------------
		
		if ( strstr( $useragent, 'safari' ) )
		{
			preg_match( "#safari/([0-9.]{1,10})#", $useragent, $ver );
			
			return array( 'browser' => 'safari', 'version' => $ver[1] );
		}
		
		//-----------------------------------------
		// Mozilla browsers...
		//-----------------------------------------
		
		if ( strstr( $useragent, 'gecko' ) )
		{ 
			preg_match( "#gecko/(\d+)#", $useragent, $ver );
			
			return array( 'browser' => 'gecko', 'version' => $ver[1] );
		}
		
		//-----------------------------------------
		// Older Mozilla browsers...
		//-----------------------------------------
		
		if ( strstr( $useragent, 'mozilla' ) )
		{
			preg_match( "#^mozilla/[5-9]\.[0-9.]{1,10}.+rv:([0-9a-z.+]{1,10})#", $useragent, $ver );
			
			return array( 'browser' => 'mozilla', 'version' => $ver[1] );
		}
		
		//-----------------------------------------
		// Konqueror...
		//-----------------------------------------
		
		if ( strstr( $useragent, 'konqueror' ) )
		{
			preg_match( "#konqueror/([0-9.]{1,10})#", $useragent, $ver );
			
			return array( 'browser' => 'konqueror', 'version' => $ver[1] );
		}
		
		//-----------------------------------------
		// Still here?
		//-----------------------------------------
		
		return array( 'browser' => $browser, 'version' => $version );
	}
	
	/*-------------------------------------------------------------------------*/
	// return_md5_server_key
	// ------------------
	// md5 hash for server side validation
	/*-------------------------------------------------------------------------*/
	
	/**
	* Return MD5 hash for use in forms
	*
	* @return	string	MD5 hash
	* @since	2.0
	*/
	function return_md5_server_key()
	{
		return md5( $this->vars['sql_database'] . $this->vars['sql_user'] . $this->vars['sql_pass']  );
	}
	
	/*-------------------------------------------------------------------------*/
	// Stronghold: Check cookie
	/*-------------------------------------------------------------------------*/
	
	/**
	* Checks auto-log in strong hold cookie
	*
	* @param	int     Member's ID
	* @param	string	Member's log in key
	* @return	boolean
	*/
	
	function stronghold_check_cookie( $member_id, $member_log_in_key )
	{
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( ! isset($this->vars[ 'cookie_stronghold' ]) OR ! $this->vars[ 'cookie_stronghold' ] )
		{
			return TRUE;
		}
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$ip_octets  = explode( ".", $this->my_getenv('REMOTE_ADDR') );
		$crypt_salt = md5( $this->vars['sql_pass'] . $this->vars['sql_user'] );
		$cookie     = $this->my_getcookie( $this->stronghold_cookie_name );
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! $cookie )
		{
			return FALSE;
		}
		
		//-----------------------------------------
		// Put it together....
		//-----------------------------------------
		
		$stronghold = md5( md5( $member_id . "-" . $ip_octets[0] . '-' . $ip_octets[1] . '-' . $member_log_in_key ) . $crypt_salt );
		
		//-----------------------------------------
		// Check against cookie
		//-----------------------------------------
		
		return $cookie == $stronghold ? TRUE : FALSE;
	}
	
	/*-------------------------------------------------------------------------*/
	// Stronghold: Create and set cookie
	/*-------------------------------------------------------------------------*/
	
	/**
	* Creates an auto-log in strong hold cookie
	*
	* @param	int     Member's ID
	* @param	string	Member's log in key
	* @return	boolean
	*/
	
	function stronghold_set_cookie( $member_id, $member_log_in_key )
	{
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( ! isset($this->vars['cookie_stronghold']) OR ! $this->vars['cookie_stronghold'] )
		{
			return FALSE;
		}
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$ip_octets  = explode( ".", $this->my_getenv('REMOTE_ADDR') );
		$crypt_salt = md5( $this->vars['sql_pass'].$this->vars['sql_user'] );
		
		//-----------------------------------------
		// Put it together....
		//-----------------------------------------
		
		$stronghold = md5( md5( $member_id . "-" . $ip_octets[0] . '-' . $ip_octets[1] . '-' . $member_log_in_key ) . $crypt_salt );
		
		//-----------------------------------------
		// Set cookie
		//-----------------------------------------
		
		$this->my_setcookie( $this->stronghold_cookie_name, $stronghold, 1 );
	
		return TRUE;
	}
	
	/*-------------------------------------------------------------------------*/
	// Check an email address to see if it's valid
	/*-------------------------------------------------------------------------*/
	/**
	* Check email address
	*
	* @param	string	Email address
	* @return	boolean
	* @since	2.0
	*/
	function check_email_address($email = "")
	{
		$email = trim($email);
		
		$email = str_replace( " ", "", $email );
		
		//-----------------------------------------
		// Check for more than 1 @ symbol
		//-----------------------------------------
		
		if ( substr_count( $email, '@' ) > 1 )
		{
			return FALSE;
		}
		
    	if ( preg_match( "#[\;\#\n\r\*\'\"<>&\%\!\(\)\{\}\[\]\?\\/\s]#", $email ) )
		{
			return FALSE;
		}
    	else if ( preg_match( "/^.+\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,4}|[0-9]{1,4})(\]?)$/", $email) )
    	{
    		return TRUE;
    	}
    	else
    	{ 
    		return FALSE;
    	}
	}
	
	/*-------------------------------------------------------------------------*/
	// Redirect using HTTP commands, not a page meta tag.
	/*-------------------------------------------------------------------------*/
	
	function immediate_redirect($url)
	{	
		# Ensure &amp;s are taken care of
		$url = str_replace( "&amp;", "&", $url );
		
		if ( $this->vars['header_redirect'] == 'refresh' )
		{
			@header("Refresh: 0;url=".$url);
		}
		else if ( $this->vars['header_redirect'] == 'html' )
		{
			echo("<html><head><meta http-equiv='refresh' content='0; url=$url'></head><body></body></html>");
			exit();
		}
		else
		{
			@header( "Location: ".$url );
		}
		exit();
	}
	
	/*-------------------------------------------------------------------------*/
	// XSS Clean: URLs
	/*-------------------------------------------------------------------------*/
	
	/**
	* Check a URL to make sure it's not all hacky
	*
	* @param	string	Input String
	* @return	boolean 
	* @since	2.1.0
	*/
	
	function xss_check_url( $url )
	{
		$url = trim( urldecode( $url ) );
		
		if ( ! preg_match( "#^https?://(?:[^<>*\"]+|[a-z0-9/\._\- !]+)$#iU", $url ) )
		{
			return FALSE;
		}
		
		return TRUE;
	}
	
	/*-------------------------------------------------------------------------*/
	// XSS Clean: Nasty HTML
	/*-------------------------------------------------------------------------*/
	
	/**
	* Remove script tags from HTML (well, best shot anyway)
	*
	* @param	string	Input HTML
	* @return	string  Cleaned HTML 
	* @since	2.1.0
	*/
	
	function xss_html_clean( $html )
	{
		//-----------------------------------------
		// Opening script tags...
		// Check for spaces and new lines...
		//-----------------------------------------
		
		$html = preg_replace( "#<(\s+?)?s(\s+?)?c(\s+?)?r(\s+?)?i(\s+?)?p(\s+?)?t#is"        , "&lt;script" , $html );
		$html = preg_replace( "#<(\s+?)?/(\s+?)?s(\s+?)?c(\s+?)?r(\s+?)?i(\s+?)?p(\s+?)?t#is", "&lt;/script", $html );
		
		//-----------------------------------------
		// Basics...
		//-----------------------------------------
		
		$html = preg_replace( "/javascript/i" , "j&#097;v&#097;script", $html );
		$html = preg_replace( "/alert/i"      , "&#097;lert"          , $html );
		$html = preg_replace( "/about:/i"     , "&#097;bout:"         , $html );
		$html = preg_replace( "/onmouseover/i", "&#111;nmouseover"    , $html );
		$html = preg_replace( "/onclick/i"    , "&#111;nclick"        , $html );
		$html = preg_replace( "/onload/i"     , "&#111;nload"         , $html );
		$html = preg_replace( "/onsubmit/i"   , "&#111;nsubmit"       , $html );
		$html = preg_replace( "/<body/i"      , "&lt;body"            , $html );
		$html = preg_replace( "/<html/i"      , "&lt;html"            , $html );
		$html = preg_replace( "/document\./i" , "&#100;ocument."      , $html );
		
		return $html;
	}
	
	/*-------------------------------------------------------------------------*/
	// HAX Check executable code
	/*-------------------------------------------------------------------------*/
	
	/**
	* Checks for executable code
	*
	* @param	string	Input String
	* @return	boolean 
	* @since	2.2.0
	*/
	
	function hax_check_for_executable_code( $text='' )
	{
		//-----------------------------------------
		// Test
		//-----------------------------------------
		
		if ( preg_match( "#include|require|include_once|require_once|exec|system|passthru|`#si", $text ) )
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	/*-------------------------------------------------------------------------*/
	// Convert string between char-sets
	/*-------------------------------------------------------------------------*/
	
	/**
	* Removes control characters (hidden spaces)
	*
	* @param	string	Input String
	* @return	intger	String length
	* @since	2.1
	*/
	function txt_rm_ctl_chars( $t )
	{
		if ( $this->ipsclass->vars['strip_space_chr'] )
		{
			$t = str_replace( chr(160), ' ', $t );
			$t = str_replace( chr(173), ' ', $t );
		}
		
		return $t;
    }

	/*-------------------------------------------------------------------------*/
	// txt_clean_for_xss
	// ------------------
	// Cleans up HTML for XSS
	/*-------------------------------------------------------------------------*/
	
	function txt_clean_for_xss($t="")
	{
		$t = $this->txt_htmlspecialchars( $t );
		
		return $t; // A nice cup of?
	}
	
	/*-------------------------------------------------------------------------*/
	// Clean up MD5 hashes
	/*-------------------------------------------------------------------------*/
	
	/**
	* Returns a cleaned MD5 hash
	*
	* @param	string	Input String
	* @return	string	Parsed string
	* @since	2.1
	*/
	function txt_md5_clean( $t )
	{
		return preg_replace( "/[^a-zA-Z0-9]/", "" , substr( $t, 0, 32 ) );
    }
	

	/**
	* Convert a string between charsets
	*
	* @param	string	Input String
	* @param	string	Current char set
	* @param	string	Destination char set
	* @return	string	Parsed string
	* @since	2.1.0
	*/
	function txt_convert_charsets($t, $original_cset, $destination_cset="")
	{
		$original_cset = strtolower($original_cset);
		$text         = $t;
		
		//-----------------------------------------
		// Did we pass a destination?
		//-----------------------------------------
		
		$destination_cset = strtolower($destination_cset) ? strtolower($destination_cset) : strtolower($this->vars['gb_char_set']);
		
		//-----------------------------------------
		// Not the same?
		//-----------------------------------------
		
		if ( $destination_cset == $original_cset )
		{
			return $t;
		}
		
		if ( ! is_object( $this->class_convert_charset ) )
		{
			require_once( KERNEL_PATH.'/class_convert_charset.php' );
			$this->class_convert_charset = new class_convert_charset();
		}
		
		$text = $this->class_convert_charset->convert_charset( $text, $original_cset, $destination_cset );
		
		return $text ? $text : $t;
	}
	
	/*-------------------------------------------------------------------------*/
	// Truncate text string
	/*-------------------------------------------------------------------------*/
	
	/**
	* Truncate a HTML string without breaking HTML entites
	*
	* @param	string	Input String
	* @param	integer	Desired min. length
	* @return	string	Parsed string
	* @since	2.0
	*/
	
	function txt_truncate($text, $limit=30)
	{
		$text = str_replace( '&amp;' , '&#38;', $text );
		$text = str_replace( '&quot;', '&#34;', $text );
	
		$string_length = $this->txt_mb_strlen( $text );
		
		if ( $string_length > $limit)
		{
			// Multi-byte support
			$text = preg_replace('#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,0}'.
                       '((?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'.intval($limit-3).'}).*#s',
                       '$1',$text)."...";

			//$text = substr($text,0, $limit - 3) . "...";
			$text = preg_replace( "/&(#{0,}([a-zA-Z0-9]+?)?)?\.\.\.$/", '...', $text );
		}
		else
		{
			$text = preg_replace( "/&(#{0,}([a-zA-Z0-9]+?)?)?$/", '', $text );
		}
		
		return $text;
	}
	
	
	/*-------------------------------------------------------------------------*/
	// Multibyte safe substr
	/*-------------------------------------------------------------------------*/
	
	/**
	* Substr support for this without mb_substr
	*
	* @param	string	Input String
	* @param	integer	Desired min. length
	* @return	string	Parsed string
	* @since	2.0
	*/
	
	function txt_mbsubstr($text, $start=0, $limit=30)
	{
		$text = str_replace( '&amp;' , '&#38;', $text );
		$text = str_replace( '&quot;', '&#34;', $text );
		
		// Do we have multi-byte functions?
		
		if( function_exists('mb_list_encodings') )
		{
			$valid_encodings = array();
			$valid_encodings = mb_list_encodings();
			
			if( count($valid_encodings) )
			{
				if( in_array( $this->vars['gb_char_set'], $valid_encodings ) )
				{
					return mb_substr( $text, $start, $limit, $this->vars['gb_char_set'] );
				}
			}
		}
		
		// No?  Let's do our handrolled method then
	
		$string_length = $this->txt_mb_strlen( $text );
		
		if ( $string_length > $limit)
		{
			// Multi-byte support
			$text = @preg_replace('#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,0}'.
                       '((?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){'.intval($start).','.intval($limit).'}).*#s',
                       '$1',$text);

			$text = preg_replace( "/&(#(\d+?)?)?$/", '', $text );
		}
		else
		{
			$text = preg_replace( "/&(#(\d+?)?)?$/", '', $text );
		}
		
		return $text;
	}	
	
	/*-------------------------------------------------------------------------*/
	// txt_filename_clean
	// ------------------
	// Clean filenames...
	/*-------------------------------------------------------------------------*/
	
	/**
	* Clean a string to prevent file traversal issues
	*
	* @param	string	Input String
	* @return	string	Parsed string
	* @since	2.1
	*/
	function txt_filename_clean( $t )
	{
		$t = $this->txt_alphanumerical_clean( $t, '.' );
		$t = preg_replace( '#\.{1,}#s', '.', $t );
		
		return $t;
    }

	/*-------------------------------------------------------------------------*/
	// txt_alphanumerical_clean
	// ------------------
	// Remove non alphas
	/*-------------------------------------------------------------------------*/
	
	/**
	* Clean a string to remove all non alphanumeric characters
	*
	* @param	string	Input String
	* @param	string	Additional tags
	* @return	string	Parsed string
	* @since	2.1
	*/
	function txt_alphanumerical_clean( $t, $additional_tags="" )
	{
		if ( $additional_tags )
		{
			$additional_tags = preg_quote( $additional_tags, "/" );
		}
		
		return preg_replace( "/[^a-zA-Z0-9\-\_".$additional_tags."]/", "" , $t );
    }
    
	/*-------------------------------------------------------------------------*/
	// txt_mb_strlen
	// ------------------
	// Multi byte char string length
	/*-------------------------------------------------------------------------*/
	
	/**
	* Get the true length of a multi-byte character string
	*
	* @param	string	Input String
	* @return	intger	String length
	* @since	2.1
	*/
	function txt_mb_strlen( $t )
	{
		return strlen( preg_replace("/&#([0-9]+);/", "-", $this->txt_stripslashes( $t ) ) );
    }
    
	/*-------------------------------------------------------------------------*/
	// txt_stripslashes
	// ------------------
	// Make Big5 safe - only strip if not already...
	/*-------------------------------------------------------------------------*/
	
	/**
	* Remove slashes if magic_quotes enabled
	*
	* @param	string	Input String
	* @return	string	Parsed string
	* @since	2.0
	*/
	function txt_stripslashes($t)
	{
		if ( $this->get_magic_quotes )
		{
    		$t = stripslashes($t);
    		$t = preg_replace( "/\\\(?!&amp;#|\?#)/", "&#092;", $t );
    	}
    	
    	return $t;
    }
	
	/*-------------------------------------------------------------------------*/
	// txt_raw2form
	// ------------------
	// makes _POST text safe for text areas
	/*-------------------------------------------------------------------------*/
	
	/**
	* Convert text for use in a textarea
	*
	* @param	string	Input String
	* @return	string	Parsed string
	* @since	2.0
	*/
	function txt_raw2form($t="")
	{
		$t = str_replace( '$', "&#036;", $t);
			
		if ( $this->get_magic_quotes )
		{
			$t = stripslashes($t);
		}
		
		$t = preg_replace( "/\\\(?!&amp;#|\?#)/", "&#092;", $t );
		
		//---------------------------------------
		// Make sure macros aren't converted
		//---------------------------------------
		
		$t = preg_replace( "/<{(.+?)}>/", "&lt;{\\1}&gt;", $t );
		
		return $t;
	}
	
	/*-------------------------------------------------------------------------*/
	// txt_form2raw
	// ------------------
	// Converts textarea to raw
	/*-------------------------------------------------------------------------*/
	
	/**
	* Unconvert text for use in a textarea
	*
	* @param	string	Input String
	* @return	string	Parsed string
	* @since	2.0
	*/
	function txt_form2raw($t="")
	{
		$t = str_replace( "&#036;", '$' , $t);
		$t = str_replace( "&#092;", '\\', $t );
		
		return $t;
	}
	
	/*-------------------------------------------------------------------------*/
	// Make template / editable data safe for forms.
	/*-------------------------------------------------------------------------*/
	
	function txt_text_to_form($t="")
	{
		// Use forward look up to only convert & not &#123;
		//$t = preg_replace("/&(?!#[0-9]+;)/s", '&#38;', $t );
		
		$t = str_replace( "&" , "&#38;"  , $t );
		$t = str_replace( "<" , "&#60;"  , $t );
		$t = str_replace( ">" , "&#62;"  , $t );
		$t = str_replace( '"' , "&#34;"  , $t );
		$t = str_replace( "'" , '&#039;' , $t );
		#$t = str_replace( "\\", "&#092;" , $t );
		
		return $t; // A nice cup of?
	}
	
	/*-------------------------------------------------------------------------*/
	// Convert back form data to test
	/*-------------------------------------------------------------------------*/
	
	function txt_form_to_text($t="")
	{
		#$t = str_replace( '\\'     , '\\\\', $t );
		$t = str_replace( "&#38;"  , "&", $t );
		$t = str_replace( "&#60;"  , "<", $t );
		$t = str_replace( "&#62;"  , ">", $t );
		$t = str_replace( "&#34;"  , '"', $t );
		$t = str_replace( "&#039;" , "'", $t );
		#$t = str_replace( '&#092;' ,'\\', $t );
		
		return $t;
	}
	
	/*-------------------------------------------------------------------------*/
	// Safe Slashes - ensures slashes are saved correctly
	/*-------------------------------------------------------------------------*/
	
	/**
	* Attempt to make slashes safe for us in DB (not really needed now?)
	*
	* @param	string	Input String
	* @return	string	Parsed string
	* @since	2.0
	*/
	function txt_safeslashes($t="")
	{
		return str_replace( '\\', "\\\\", $this->txt_stripslashes($t));
	}
	
	/*-------------------------------------------------------------------------*/
	// txt_htmlspecialchars
	// ------------------
	// Custom version of htmlspecialchars to take into account mb chars
	/*-------------------------------------------------------------------------*/
	
	/**
	* htmlspecialchars including multi-byte characters
	*
	* @param	string	Input String
	* @return	string	Parsed string
	* @since	2.0
	*/
	function txt_htmlspecialchars($t="")
	{
		// Use forward look up to only convert & not &#123;
		$t = preg_replace("/&(?!#[0-9]+;)/s", '&amp;', $t );
		$t = str_replace( "<", "&lt;"  , $t );
		$t = str_replace( ">", "&gt;"  , $t );
		$t = str_replace( '"', "&quot;", $t );
		$t = str_replace( "'", '&#039;', $t );
		
		return $t; // A nice cup of?
	}
	
	/*-------------------------------------------------------------------------*/
	// txt_UNhtmlspecialchars
	// ------------------
	// Undoes what the above function does. Yes.
	/*-------------------------------------------------------------------------*/
	
	/**
	* unhtmlspecialchars including multi-byte characters
	*
	* @param	string	Input String
	* @return	string	Parsed string
	* @since	2.0
	*/
	function txt_UNhtmlspecialchars($t="")
	{
		$t = str_replace( "&amp;" , "&", $t );
		$t = str_replace( "&lt;"  , "<", $t );
		$t = str_replace( "&gt;"  , ">", $t );
		$t = str_replace( "&quot;", '"', $t );
		$t = str_replace( "&#039;", "'", $t );
		
		return $t;
	}
	
	/*-------------------------------------------------------------------------*/
    // Clean evil tags
    /*-------------------------------------------------------------------------*/
    
    function txt_clean_evil_tags( $t )
    {
    	$t = preg_replace( "/javascript/i" , "j&#097;v&#097;script", $t );
		$t = preg_replace( "/alert/i"      , "&#097;lert"          , $t );
		$t = preg_replace( "/about:/i"     , "&#097;bout:"         , $t );
		$t = preg_replace( "/onmouseover/i", "&#111;nmouseover"    , $t );
		$t = preg_replace( "/onclick/i"    , "&#111;nclick"        , $t );
		$t = preg_replace( "/onload/i"     , "&#111;nload"         , $t );
		$t = preg_replace( "/onsubmit/i"   , "&#111;nsubmit"       , $t );
		$t = preg_replace( "/<body/i"      , "&lt;body"            , $t );
		$t = preg_replace( "/<html/i"      , "&lt;html"            , $t );
		$t = preg_replace( "/document\./i" , "&#100;ocument."      , $t );
		
		return $t;
    }

	/*-------------------------------------------------------------------------*/
	// txt_wintounix
	// ------------------
	// Converts \r\n to \n
	/*-------------------------------------------------------------------------*/
	
	/**
	* Convert windows newlines to unix newlines
	*
	* @param	string	Input String
	* @return	string	Parsed string
	* @since	2.0
	*/
	function txt_windowstounix($t="")
	{
		// windows
		$t = str_replace( "\r\n" , "\n", $t );
		// Mac OS 9
		$t = str_replace( "\r"   , "\n", $t );
		return $t;
	}
	
	/*-------------------------------------------------------------------------*/
	// C.O.C.S (clean old comma-delimeted strings)
	// ------------------
	// <>
	/*-------------------------------------------------------------------------*/
	
	/**
	* Remove leading comma from comma delim string
	*
	* @param	string	Input String
	* @return	string	Parsed string
	* @since	2.0
	*/
	function trim_leading_comma($t)
	{
		return preg_replace( "/^,/", "", $t );
	}
	
	/**
	* Remove trailing comma from comma delim string
	*
	* @param	string	Input String
	* @return	string	Parsed string
	* @since	2.0
	*/
	function trim_trailing_comma($t)
	{
		return preg_replace( "/,$/", "", $t );
	}
	
	/**
	* Remove dupe commas from comma delim string
	*
	* @param	string	Input String
	* @return	string	Parsed string
	* @since	2.0
	*/
	function clean_comma($t)
	{
		return preg_replace( "/,{2,}/", ",", $t );
	}
	
	/**
	* Clean perm string (wrapper for comma cleaners)
	*
	* @param	string	Input String
	* @return	string	Parsed string
	* @since	2.0
	*/
	function clean_perm_string($t)
	{
		$t = $this->clean_comma($t);
		$t = $this->trim_leading_comma($t);
		$t = $this->trim_trailing_comma($t);
		
		return $t;
	}
	
	/*-------------------------------------------------------------------------*/
    // Clean file path
    /*-------------------------------------------------------------------------*/
    
    function clean_filepath($v)
    {
    	return preg_replace( "#[^\d\w\-\_/\.\+]#", "", trim( stripslashes( rawurldecode($v) ) ) );
    }

	/*-------------------------------------------------------------------------*/
	// Calculate max post size
	/*-------------------------------------------------------------------------*/
	
	function math_get_post_max_size()
	{
		$max_file_size = 16777216;
		$tmp           = 0;
		
		$_post   = @ini_get('post_max_size');
		$_upload = @ini_get('upload_max_filesize');
		
		if ( $_upload > $_post )
		{
			$tmp = $_post;
		}
		else
		{
			$tmp = $_upload;
		}
		
		if ( $tmp )
		{
			$max_file_size = $tmp;
			unset($tmp);
			
			preg_match( "#^(\d+)(\w+)$#", strtolower($max_file_size), $match );
			
			if ( $match[2] == 'm' )
			{
				$max_file_size = intval( $max_file_size ) * 1024 * 1024;
			}
			else if ( $match[2] == 'k' )
			{
				$max_file_size = intval( $max_file_size ) * 1024;
			}
			else
			{
				$max_file_size = intval( $max_file_size );
			}
		}
		
		return $max_file_size;
	}
	
	/*-------------------------------------------------------------------------*/
    // math_strlen_to_bytes
    /*-------------------------------------------------------------------------*/
    
    /**
	* Convert strlen to bytes
	*
	* @param	integer	string length (no chars)
	* @return	integer	Bytes
	* @since	2.0
	*/
	function math_strlen_to_bytes( $strlen=0 )
    {
		$dh = pow(10, 0);
        
        return round( $strlen / ( pow(1024, 0) / $dh ) ) / $dh;
    }
    
	/*-------------------------------------------------------------------------*/
	// size_format
	// ------------------
	// Give it a byte to eat and it'll return nice stuff!
	/*-------------------------------------------------------------------------*/
	
	/**
	* Convert bytes into kb, mb, etc
	*
	* @param	integer	size in bytes
	* @return	string	Human size
	* @since	2.0
	*/
	function size_format($bytes="")
	{
		return $this->math_size_format( $bytes );
	}
	
	/*-------------------------------------------------------------------------*/
	// math_size_format
	/*-------------------------------------------------------------------------*/
	
	function math_size_format($bytes="")
	{
		$retval = "";
		$lang['sf_mb']    = $this->language['sf_mb']    ? $this->language['sf_mb']    : 'mb';
		$lang['sf_k']     = $this->language['sf_k']     ? $this->language['sf_k']     : 'kb';
		$lang['sf_bytes'] = $this->language['sf_bytes'] ? $this->language['sf_bytes'] : 'b';
		
		if ($bytes >= 1048576)
		{
			$retval = round($bytes / 1048576 * 100 ) / 100 . $lang['sf_mb'];
		}
		else if ($bytes  >= 1024)
		{
			$retval = round($bytes / 1024 * 100 ) / 100 . $lang['sf_k'];
		}
		else
		{
			$retval = $bytes . $lang['sf_bytes'];
		}
		
		return $retval;
	}
	
	/*-------------------------------------------------------------------------*/
    // Convert unix timestamp into: (no leading zeros)
    // array( 'day' => x, 'month' => x, 'year' => x, 'hour' => x, 'minute' => x );
    // Written into separate function to allow for timezone to be used easily
    /*-------------------------------------------------------------------------*/
    
    function unixstamp_to_human( $unix=0 )
    {
    	$tmp = gmdate( 'j,n,Y,G,i', $unix );
    	
    	list( $day, $month, $year, $hour, $min ) = explode( ',', $tmp );
  
    	return array( 'day'    => $day,
    				  'month'  => $month,
    				  'year'   => $year,
    				  'hour'   => $hour,
    				  'minute' => $min );
    }
    
    /*-------------------------------------------------------------------------*/
    // Convert unix stampt to mmddyyyy
    /*-------------------------------------------------------------------------*/
    
    function unixstamp_to_mmddyyyy( $unix=0, $sep='/' )
    {
    	if ( ! $unix )
    	{
    		return "";
    	}
    	
    	$date = $this->unixstamp_to_human( $unix );
    	
    	return sprintf("%02d{$sep}%02d{$sep}%04d", $date['month'], $date['day'], $date['year'] );
    }
    
     /*-------------------------------------------------------------------------*/
    // Convert mmddyyyy to unixstamp
    /*-------------------------------------------------------------------------*/
    
    function mmddyyyy_to_unixstamp( $date='', $sep='/', $checkdate=true )
    {
    	if ( ! $date )
    	{
    		return "";
    	}
    	
    	list( $month, $day, $year ) = explode( $sep, $date );
    	
    	if ( $checkdate )
    	{
			if ( ! checkdate( $month, $day, $year ) )
			{
				return "";
			}
    	}
    	
    	return $this->human_to_unixstamp( $day, $month, $year, 0, 0 );
    }
    
    /*-------------------------------------------------------------------------*/
    // Wrapper for mktime()
    // Written into separate function to allow for timezone to be used easily
    /*-------------------------------------------------------------------------*/
    
    function human_to_unixstamp( $day, $month, $year, $hour, $minute )
    {
    	return gmmktime( intval($hour), intval($minute), 0, intval($month), intval($day), intval($year) );
    }

	/*-------------------------------------------------------------------------*/
    // My gmmktime() - PHP func seems buggy
    /*-------------------------------------------------------------------------*/ 
    
    /**
	* My gmmktime() - PHP func seems buggy
    *
	*
	* @param	integer
	* @param	integer
	* @param	integer
	* @param	integer
	* @param	integer
	* @param	integer
	* @return	integer
	* @since	2.0
	*/
	function date_gmmktime( $hour=0, $min=0, $sec=0, $month=0, $day=0, $year=0 )
	{
		// Calculate UTC time offset
		$offset = date( 'Z' );
		
		// Generate server based timestamp
		$time   = mktime( $hour, $min, $sec, $month, $day, $year );
		
		// Calculate DST on / off
		$dst    = intval( date( 'I', $time ) - date( 'I' ) );
		
		return $offset + ($dst * 3600) + $time;
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// do_number_format() : Nice little sub to handle common stuff
	//
	/*-------------------------------------------------------------------------*/
	
	/**
	* Wrapper for number_format
	*
	* @param	integer	Number
	* @return	string	Formatted number
	* @since	2.0
	*/
	function do_number_format($number)
	{
		if ( $this->vars['number_format'] != 'none' )
		{ 
			return number_format($number , 0, '', $this->num_format);
		}
		else
		{
			return $number;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Convert newlines to <br /> nl2br is buggy with <br /> on early PHP builds
	/*-------------------------------------------------------------------------*/
	
	/**
	* <br /> Safe nl2br (Buggy on old PHP builds)
	*
	* @param	string	Input text
	* @return	string	Parsed text
	* @since	2.0
	*/
	function my_nl2br($t="")
	{
		return str_replace( "\n", "<br />", $t );
	}
	
	/*-------------------------------------------------------------------------*/
	// Convert <br /> to newlines
	/*-------------------------------------------------------------------------*/
	
	/**
	* <br /> Safe br2nl
	*
	* @param	string	Input text
	* @return	string	Parsed text
	* @since	2.0
	*/
	function my_br2nl($t="")
	{
		$t = preg_replace( "#(?:\n|\r)?<br />(?:\n|\r)?#", "\n", $t );
		$t = preg_replace( "#(?:\n|\r)?<br>(?:\n|\r)?#"  , "\n", $t );
		
		return $t;
	}
	
    /*-------------------------------------------------------------------------*/
    // getdate doesn't work apparently as it doesn't take into account
    // the offset, even when fed a GMT timestamp.
    /*-------------------------------------------------------------------------*/
    
    /**
	* Hand rolled GETDATE method
    *
    * getdate doesn't work apparently as it doesn't take into account
    * the offset, even when fed a GMT timestamp.
	*
	* @param	integer	Unix date
	* @return	array	0, seconds, minutes, hours, mday, wday, mon, year, yday, weekday, month
	* @since	2.0
	*/
    function date_getgmdate( $gmt_stamp )
    {
    	$tmp = gmdate( 'j,n,Y,G,i,s,w,z,l,F,W,M', $gmt_stamp );
    	
    	list( $day, $month, $year, $hour, $min, $seconds, $wday, $yday, $weekday, $fmon, $week, $smon ) = explode( ',', $tmp );
    	
    	return array(  0         => $gmt_stamp,
    				   "seconds" => $seconds, //	Numeric representation of seconds	0 to 59
					   "minutes" => $min,     //	Numeric representation of minutes	0 to 59
					   "hours"	 => $hour,	  //	Numeric representation of hours	0 to 23
					   "mday"	 => $day,     //	Numeric representation of the day of the month	1 to 31
					   "wday"	 => $wday,    //    Numeric representation of the day of the week	0 (for Sunday) through 6 (for Saturday)
					   "mon"	 => $month,   //    Numeric representation of a month	1 through 12
					   "year"	 => $year,    //    A full numeric representation of a year, 4 digits	Examples: 1999 or 2003
					   "yday"	 => $yday,    //    Numeric representation of the day of the year	0 through 365
					   "weekday" => $weekday, //	A full textual representation of the day of the week	Sunday through Saturday
					   "month"	 => $fmon,    //    A full textual representation of a month, such as January or Mar
					   "week"    => $week,    //    Week of the year
					   "smonth"  => $smon,
					   "smon"    => $smon
					);
    }
    
    /*-------------------------------------------------------------------------*/
    // Get Environment Variable
    /*-------------------------------------------------------------------------*/  
    
    /**
	* Get an environment variable value
    *
    * Abstract layer allows us to user $_SERVER or getenv()
	*
	* @param	string	Env. Variable key
	* @return	string
	* @since	2.2
	*/
	
    function my_getenv($key)
    {
	    $return = array();
	    
	    if ( is_array( $_SERVER ) AND count( $_SERVER ) )
	    {
		    if( isset( $_SERVER[$key] ) )
		    {
			    $return = $_SERVER[$key];
		    }
	    }
	    
	    if ( ! $return )
	    {
		    $return = getenv($key);
	    }
	    
	    return $return;
    }    
    
    /*-------------------------------------------------------------------------*/
    // Sets a cookie, abstract layer allows us to do some checking, etc                
    /*-------------------------------------------------------------------------*/    
    
    /**
	* Set a cookie
    *
    * Abstract layer allows us to do some checking, etc
	*
	* @param	string	Cookie name
	* @param	string	Cookie value
	* @param	integer	Is sticky flag
	* @return	void
	* @since	2.0
	*/
    function my_setcookie( $name, $value="", $sticky=1, $expires_x_days=0 )
    {
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
        if ( $this->no_print_header )
        {
        	return;
        }
        
		//-----------------------------------------
		// Set vars
		//-----------------------------------------

        if ( $sticky == 1 )
        {
        	$expires = time() + 60*60*24*365;
        }
		else if ( $expires_x_days )
		{
			$expires = time() + ( $expires_x_days * 86400 );
		}
		else
		{
			$expires = FALSE;
		}
		
		//-----------------------------------------
		// Finish up...
		//-----------------------------------------
		
        $this->vars['cookie_domain'] = $this->vars['cookie_domain'] == "" ? ""  : $this->vars['cookie_domain'];
        $this->vars['cookie_path']   = $this->vars['cookie_path']   == "" ? "/" : $this->vars['cookie_path'];
      	
		//-----------------------------------------
		// Set the cookie
		//-----------------------------------------
		
		if ( in_array( $name, $this->sensitive_cookies ) )
		{
			if ( PHP_VERSION < 5.2 )
			{
				if ( $this->vars['cookie_domain'] )
				{
					@setcookie( $this->vars['cookie_id'].$name, $value, $expires, $this->vars['cookie_path'], $this->vars['cookie_domain'] . '; HttpOnly' );
				}
				else
				{
					@setcookie( $this->vars['cookie_id'].$name, $value, $expires, $this->vars['cookie_path'] );
				}
			}
			else
			{
				@setcookie( $this->vars['cookie_id'].$name, $value, $expires, $this->vars['cookie_path'], $this->vars['cookie_domain'], NULL, TRUE );
			}
		}
		else
		{
			@setcookie( $this->vars['cookie_id'].$name, $value, $expires, $this->vars['cookie_path'], $this->vars['cookie_domain']);
		}
    }
    
    /*-------------------------------------------------------------------------*/
    // Cookies, cookies everywhere and not a byte to eat.
    /*-------------------------------------------------------------------------*/  
    
    /**
	* Get a cookie
    *
    * Abstract layer allows us to do some checking, etc
	*
	* @param	string	Cookie name
	* @return	mixed
	* @since	2.0
	*/
	
    function my_getcookie($name)
    {
    	if (isset($_COOKIE[$this->vars['cookie_id'].$name]))
    	{
    		return urldecode($_COOKIE[$this->vars['cookie_id'].$name]);
    	}
    	else
    	{
    		return FALSE;
    	}
    }
    
	/*-------------------------------------------------------------------------*/
    // Makes topics read or forum read cookie safe         
    /*-------------------------------------------------------------------------*/
    /**
	* Makes int based arrays safe
	* XSS Fix: Ticket: 243603
	* Problem with cookies allowing SQL code in keys
	*
	* @param	array	Array
	* @return	array	Array (Cleaned)
	* @since	2.1.4(A)
	*/
    function clean_int_array( $array=array() )
    {
		$return = array();
		
		if ( is_array( $array ) and count( $array ) )
		{
			foreach( $array as $k => $v )
			{
				$return[ intval($k) ] = intval($v);
			}
		}
		
		return $return;
	}
		
    /*-------------------------------------------------------------------------*/
    // Makes incoming info "safe"              
    /*-------------------------------------------------------------------------*/
    
    /**
	* Parse _GET _POST data
    *
    * Clean up and unHTML
	*
	* @return	void
	* @since	Time began
	*/
    function parse_incoming()
    {
		//-----------------------------------------
		// Attempt to switch off magic quotes
		//-----------------------------------------

		@set_magic_quotes_runtime(0);

		$this->get_magic_quotes = @get_magic_quotes_gpc();
		
    	//-----------------------------------------
    	// Clean globals, first.
    	//-----------------------------------------
    	
		$this->clean_globals( $_GET );
		$this->clean_globals( $_POST );
		$this->clean_globals( $_COOKIE );
		$this->clean_globals( $_REQUEST );
    	
		# GET first
		$input = $this->parse_incoming_recursively( $_GET, array() );
		
		# Then overwrite with POST
		$input = $this->parse_incoming_recursively( $_POST, $input );
		
		$this->input = $input;
		
		unset( $input );
		
		# Assign request method
		$this->input['request_method'] = strtolower($this->my_getenv('REQUEST_METHOD'));
	}
	
	/*-------------------------------------------------------------------------*/
    // parse_incoming_recursively
    /*-------------------------------------------------------------------------*/
	/**
	* Recursively cleans keys and values and
	* inserts them into the input array
	*/
	function parse_incoming_recursively( &$data, $input=array(), $iteration = 0 )
	{
		// Crafty hacker could send something like &foo[][][][][][]....to kill Apache process
		// We should never have an input array deeper than 10..
		
		if ( $iteration >= 10 )
		{
			return $input;
		}
		
		foreach( $data as $k => $v )
		{
			if ( is_array( $v ) )
			{
				$input[ $k ] = $this->parse_incoming_recursively( $data[ $k ], array(), $iteration++ );
			}
			else
			{	
				$k = $this->parse_clean_key( $k );
				$v = $this->parse_clean_value( $v );
				
				$input[ $k ] = $v;
			}
		}
		
		return $input;
	}
	
	/*-------------------------------------------------------------------------*/
    // clean_globals
    /*-------------------------------------------------------------------------*/
	/**
	* Performs basic cleaning
	* Null characters, etc
	*/
	function clean_globals( &$data )
	{
		foreach( $data as $k => $v )
		{
			if ( is_array( $v ) )
			{
				$this->clean_globals( $data[ $k ] );
			}
			else
			{	
				# Null byte characters
				$v = preg_replace( '/\\\0/' , '', $v );
				$v = preg_replace( '/\\x00/', '', $v );
				$v = str_replace( '%00'     , '', $v );
				
				# File traversal
				$v = str_replace( '../'    , '&#46;&#46;/', $v );
				
				$data[ $k ] = $v;
			}
		}
	}
	
    /*-------------------------------------------------------------------------*/
    // Key Cleaner - ensures no funny business with form elements             
    /*-------------------------------------------------------------------------*/
    
    /**
	* Clean _GET _POST key
    *
	* @param	string	Key name
	* @return	string	Cleaned key name
	* @since	2.1
	*/
    function parse_clean_key($key)
    {
    	if ($key == "")
    	{
    		return "";
    	}
    	
    	$key = htmlspecialchars(urldecode($key));
    	$key = str_replace( ".."           , ""  , $key );
    	$key = preg_replace( "/\_\_(.+?)\_\_/"  , ""  , $key );
    	$key = preg_replace( "/^([\w\.\-\_]+)$/", "$1", $key );
    	
    	return $key;
    }
    
    /*-------------------------------------------------------------------------*/
    // Clean evil tags
    /*-------------------------------------------------------------------------*/
    
    /**
	* Clean possible javascipt codes
    *
	* @param	string	Input
	* @return	string	Cleaned Input
	* @since	2.0
	*/
    function clean_evil_tags( $t )
    {
    	$t = preg_replace( "/javascript/i" , "j&#097;v&#097;script", $t );
		$t = preg_replace( "/alert/i"      , "&#097;lert"          , $t );
		$t = preg_replace( "/about:/i"     , "&#097;bout:"         , $t );
		$t = preg_replace( "/onmouseover/i", "&#111;nmouseover"    , $t );
		$t = preg_replace( "/onclick/i"    , "&#111;nclick"        , $t );
		$t = preg_replace( "/onload/i"     , "&#111;nload"         , $t );
		$t = preg_replace( "/onsubmit/i"   , "&#111;nsubmit"       , $t );
		$t = preg_replace( "/<body/i"      , "&lt;body"            , $t );
		$t = preg_replace( "/<html/i"      , "&lt;html"            , $t );
		$t = preg_replace( "/document\./i" , "&#100;ocument."      , $t );
		
		return $t;
    }
    
    /*-------------------------------------------------------------------------*/
    // Clean value
    /*-------------------------------------------------------------------------*/
    
    /**
	* UnHTML and stripslashes _GET _POST value
    *
	* @param	string	Input
	* @return	string	Cleaned Input
	* @since	2.1
	*/
    function parse_clean_value($val)
    {
    	if ( $val == "" )
    	{
    		return "";
    	}
    
    	$val = str_replace( "&#032;", " ", $this->txt_stripslashes($val) );
    	
    	if ( isset($this->vars['strip_space_chr']) AND $this->vars['strip_space_chr'] )
    	{
    		$val = str_replace( chr(0xCA), "", $val );  //Remove sneaky spaces
    	}
    	
    	$val = str_replace( "&"				, "&amp;"         , $val );
    	$val = str_replace( "<!--"			, "&#60;&#33;--"  , $val );
    	$val = str_replace( "-->"			, "--&#62;"       , $val );
    	$val = preg_replace( "/<script/i"	, "&#60;script"   , $val );
    	$val = str_replace( ">"				, "&gt;"          , $val );
    	$val = str_replace( "<"				, "&lt;"          , $val );
    	$val = str_replace( '"'				, "&quot;"        , $val );
    	$val = str_replace( "\n"			, "<br />"        , $val ); // Convert literal newlines
    	$val = str_replace( "$"				, "&#036;"        , $val );
    	$val = str_replace( "\r"			, ""              , $val ); // Remove literal carriage returns
    	$val = str_replace( "!"				, "&#33;"         , $val );
    	$val = str_replace( "'"				, "&#39;"         , $val ); // IMPORTANT: It helps to increase sql query safety.
    	
    	// Ensure unicode chars are OK
    	
    	if ( $this->allow_unicode )
		{
			$val = preg_replace("/&amp;#([0-9]+);/s", "&#\\1;", $val );
			
			//-----------------------------------------
			// Try and fix up HTML entities with missing ;
			//-----------------------------------------

			$val = preg_replace( "/&#(\d+?)([^\d;])/i", "&#\\1;\\2", $val );
		}
    	
    	return $val;
    }
      
}
?>