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
|   > Template Engine module (KERNEL)
|   > Module written by Matt Mecham
|   > Date started: 5th January 2004
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
|   New template module to build, rebuild and generate caches of templates
|   which include the new IPB HTML Logic system.
|   
+--------------------------------------------------------------------------
*/

/**
* IPS Kernel Pages: Template Engine
*
* This class contains all generic functions to handle
* converting HTML logic to  PHP code and vice-versa
*
* Example Usage:
* <code>
*     <if="$bf.vars['threaded_per_page'] == 10">
*       html here
*     </if>
*     <else if="ibf.vars['threaded_per_page'] >= 100 or some_var == 'this'">
*      html here
*     </if>
*    <else if="show['this'] > 100">
*       html here
*     </if>
*     <else>
*      html here
*     </else>
* </code>
*
* @package		IPS_KERNEL
* @author		Matt Mecham
* @copyright	Invision Power Services, Inc.
* @version		2.1
*/

/**
*
*/

/**
* Template Engine
*
* Methods and functions for handling file uploads
*
* @package	IPS_KERNEL
* @author   Matt Mecham
* @version	2.1
*/
class class_template
{
	/**
	* Root path
	*
	* @var string	File Path
	*/
	var $root_path   = './';
	
	/**
	* Cache directory
	*
	* @var string	Directory
	*/
	var $cache_dir   = 'skin_cache';
	
	/**
	* Cache ID
	*
	* @var integer
	*/
	var $cache_id    = '1';
	
	/**
	* Database ID
	*
	* @var integer
	*/
	var $database_id = '1';
	
	/**
	* Cache path
	*
	* @var string	File Path
	*/
	var $cache_path  = '';
	
	function class_template()
	{
		$this->cache_path = $this->root_path . $this->cache_dir . '/cacheid_' . $this->cache_id;
	}
	
	//===================================================
	// Convert HTML to PHP cache file
	//===================================================
	
	/**
	* Wrapper function to convert HTML logic to PHP code
	*
	* @param	string	Function name (eg: show_active_users)
	* @param	string	Function data (The input variables)
	* @param	string	Function HTML (The actual HTML)
	* @param	string	Function description (Not used in IPB)
	* @return	string	Converted HTML code as a PHP function
	*/
	
	function convert_html_to_php($func_name, $func_data, $func_html, $func_desc="")
	{
		//-------------------------------
		// Make sure we have ="" on each
		// func data
		//-------------------------------
		
		$func_data = preg_replace( "#".'\$'."(\w+)(,|$)#i", "\$\\1=\"\"\\2", str_replace( " ", "", $func_data ) );
		
		$top    = "//===========================================================================\n".
			      "// <ips:{$func_name}:desc:{$func_desc}>\n".
			      "//===========================================================================\n";
			      
		$start  = "function {$func_name}($func_data) {\n\$IPBHTML = \"\";\n//--starthtml--//\n";
		$middle = $this->build_section_html_to_php($func_html);
		$end    = "\n//--endhtml--//\nreturn \$IPBHTML;\n}\n";
		
		return $top.$start.$middle.$end;
	}
	
	//===================================================
	// Alias: Convert PHP to HTML
	//===================================================
	
	/**
	* Wrapper function to convert PHP to HTML logic
	*
	* @param	string	PHP Code
	* @return	string	Converted PHP code as HTML code
	*/
	function convert_php_to_html($php)
	{
		return $this->_convert_php_to_html($php);
	}
	
	//===================================================
	// Build Section: PHP to HTML
	// - Updates HTML database with PHP cache file
	//===================================================
	
	function build_section_php_to_html($php="")
	{
	
	}
	
	//===================================================
	// Build Section: HTML to PHP
	// - Makes PHP cache of raw HTML
	//===================================================
	
	/**
	* Work function to convert HTML logic to PHP code
	*
	* @param	string	HTML logic
	* @return	string	PHP code block
	*/
	function build_section_html_to_php($html="")
	{
		
		if ( preg_match( "#<if=[\"'].+?[\"']>#si", $html ) )
		{
			//----------------------------------------
			// Does it have logic sections?
			//----------------------------------------
			
			$html = $this->_convert_html_to_php($html);
			
			//----------------------------------------
			// Non-logic from top?
			//----------------------------------------
			
			$html = preg_replace( "#^(.+?)(//startif)#ise", "\$this->_wrap_in_php('\\1', '\\2');", $html );
			
			//----------------------------------------
			// Non-logic from between?
			//----------------------------------------
			
			$html = preg_replace( "#(}//endif|}//endelse)(.+?)(//startif|//startelse|else if)#ise", "\$this->_wrap_in_php('\\2', '\\3', '\\1');", $html );
			
			//$html = str_replace( "}//endif//startif", "}//endif\n//startif", $html );
			
			//----------------------------------------
			// Non-logic from after? ENDELSE
			//----------------------------------------
			
			if ( preg_match( "#^(.*)(}//endelse)(.+?)$#is", $html, $match ) )
			{
				if ( $match[1] and $match[2] and ( ! strstr( $match[3], "<<<EOF\n" ) ) )
				{
					$html = preg_replace( "#^(.*}//endelse)(.+?)$#ise", "\$this->_wrap_in_php('\\2', '', '\\1');", $html );
				}
			}
			
			//----------------------------------------
			// Non-logic from after? ENDIF
			//----------------------------------------
			
			if ( preg_match( "#^(.*)(}//endif)(.+?)$#is", $html, $match ) )
			{
				if ( $match[1] and $match[2] and ( ! strstr( $match[3], "<<<EOF\n" ) ) )
				{	
					$html = preg_replace( "#^(.*}//endif)(.+?)$#ise", "\$this->_wrap_in_php('\\2', '', '\\1');", $html );
				}
			}
			
			//----------------------------------------
			// Clean up
			//----------------------------------------
			
			$html = preg_replace( "#//startelse\s+?//startelse#is", "//startelse", $html );
		}
		else
		{
			$html = $this->_wrap_in_php( $html );
		}
		
		//----------------------------------------
		// Unconvert special tags
		//----------------------------------------
		
		$html = $this->unconvert_tags($html);
		
		return $html;
	}
	
	//===================================================
	// Convert special tags into HTML safe versions
	//===================================================
	
	/**
	* Convert PHP tags to HTML logic safe tags
	*
	* @param	string	HTML/PHP data
	* @return	string	Converted Data
	*/
	function convert_tags($t="")
	{
		$t = preg_replace( "/{?\\\$ibforums->base_url}?/"                   , "{ipb.script_url}" , $t );
		$t = preg_replace( "/{?\\\$ibforums->session_id}?/"                 , "{ipb.session_id}" , $t );
		$t = preg_replace( "#\\\$ibforums->(member|vars|skin|lang|input)#i" , "ipb.\\1"    , $t );
		
		# IPB 2.1+ Kernel
		$t = preg_replace( "/{?\\\$this->ipsclass->base_url}?/"                   , "{ipb.script_url}" , $t );
		$t = preg_replace( "/{?\\\$this->ipsclass->session_id}?/"                 , "{ipb.session_id}" , $t );
		$t = preg_replace( "#\\\$this->ipsclass->(member|vars|skin|lang|input)#i" , "ipb.\\1", $t );
		
		//----------------------------------------
		// Make some tags safe..
		//----------------------------------------
		
		$t = preg_replace( "/\{ipb\.vars\[(['\"])?(sql_driver|sql_host|sql_database|sql_pass|sql_user|sql_port|sql_tbl_prefix|smtp_host|smtp_port|smtp_user|smtp_pass|html_dir|base_dir|upload_dir)(['\"])?\]\}/", "" , $t );
				
		return $t;
	}
	
	//===================================================
	// Uncovert them back again
	//===================================================
	
	/**
	* Convert HTML tags to PHP tags
	*
	* @param	string	HTML/PHP data
	* @return	string	Converted Data
	*/
	function unconvert_tags($t="")
	{
		//----------------------------------------
		// Make some tags safe..
		//----------------------------------------
		
		$t = preg_replace( "/\{ipb\.vars\[(['\"])?(sql_driver|sql_host|sql_database|sql_pass|sql_user|sql_port|sql_tbl_prefix|smtp_host|smtp_port|smtp_user|smtp_pass|html_dir|base_dir|upload_dir)(['\"])?\]\}/", "" , $t );
		
		# IPB 2.1+ Kernel
		$t = preg_replace( "/{ipb\.script_url}/i"                 , '{$this->ipsclass->base_url}'  , $t);
		$t = preg_replace( "/{ipb\.session_id}/i"                 , '{$this->ipsclass->session_id}', $t);
		$t = preg_replace( "#ipb\.(member|vars|skin|lang|input)#i", '$this->ipsclass->\\1'         , $t );
		
		return $t;
	}
	
	//===================================================
	// Wrap HTML into PHP
	//===================================================
	
	/**
	* Wrap PHP code in HEREDOC tags
	*
	* @param	string	Main HTML code
	* @param	string	After Code
	* @param	string	Before Code
	* @return	string	Converted Data
	*/
	function _wrap_in_php( $html, $after="", $before="" )
	{
		$html = $this->_trim_newlines($this->_trim_slashes($html));
		
		$before = $this->_trim_slashes($before);
		$after  = $this->_trim_slashes($after);
		
		if ( ! strstr( $before, "\n" ) )
		{
			$before .= "\n";
		}
		
		if ( ! trim($html) )
		{
			return $before.$html.$after;
		}
			
		return $before."\n\$IPBHTML .= <<<EOF\n$html\nEOF;\n".$after;
	}
	
	
	//===================================================
	// Convert: HTML Logic to PHP logic
	//===================================================
	
	/**
	* Convert HTML tags to PHP tags
	*
	* @param	string	HTML/PHP data
	* @return	string	Converted Data
	*/
	function _convert_html_to_php($html)
	{
		$html = $this->_trim_slashes($html);
		$html = preg_replace( "#(?:\s+?)?<if=[\"'](.+?)[\"']>(.+?)</if>#ise"     , "\$this->_statement_if('\\1', '\\2')"    , $html );
		$html = preg_replace( "#(?:\s+?)?<else if=[\"'](.+?)[\"']>(.+?)</if>#ise", "\$this->_statement_elseif('\\1', '\\2')", $html );
		$html = preg_replace( "#(?:\s+?)?<else>(.+?)</else>#ise"                 , "\$this->_statement_else('\\1')"         , $html );
		
		return $html;
	}
	
	//===================================================
	// Convert: PHP logic to HTML logic
	//===================================================
	
	/**
	* Convert PHP tags to HTML tags
	*
	* @param	string	PHP data
	* @return	string	Converted Data
	*/
	function _convert_php_to_html($php)
	{
		$php = preg_replace( "#else if\s+?\((.+?)\)\s+?{(.+?)}//endif(\n)?#ise"      , "\$this->_reverse_if('\\1', '\\2', 'else if')", $php );
		$php = preg_replace( "#//startif\nif\s+?\((.+?)\)\s+?{(.+?)}//endif(\n)?#ise", "\$this->_reverse_if('\\1', '\\2', 'if')"     , $php );
		$php = preg_replace( "#else\s+?{(.+?)}//endelse(\n)?#ise"                    , "\$this->_reverse_else( '\\1' )"              , $php );
		
		//----------------------------------------
		// Parse raw sections
		//----------------------------------------
		
		$php = $this->_reverse_ipbhtml($php);
		
		//----------------------------------------
		// Convert ipb-htmllogic tags
		//----------------------------------------
		
		//$php = preg_replace( "#//start-htmllogic#i" , "<ipb-htmllogic>", $php );
		//$php = preg_replace( "#//end-htmllogic#i", "</ipb-htmllogic>"  , $php );
		
		//----------------------------------------
		// Remove start ifs
		//----------------------------------------
		
		$php = str_replace( "//startif\n"  , "\n", $php );
		$php = str_replace( "//startelse\n", "\n", $php );
		
		//----------------------------------------
		// Remove extra spaces
		//----------------------------------------
		
		$php = preg_replace( "#(</if>|</else>)\s+?(<if|<else)#is", "\\1\n\\2", $php );
		
		//----------------------------------------
		// Make safe special $Ibforums vars
		//----------------------------------------
		
		$php = $this->convert_tags($php);
		
		return $php;
	}
	
	//===================================================
	// Reverse: PHP IF to HTML IF
	//===================================================
	
	/**
	* Reverse the IF tag
	*
	* @param	string	PHP Variables / Operators
	* @param	string	PHP data
	* @param	string	Tag start
	* @return	string	Converted Data
	*/
	function _reverse_if( $code, $php, $start='if' )
	{
		$code = $this->_trim_slashes(trim($code));
		$code = preg_replace( "/(^|and|or)(\s+)(.+?)(\s|$)/ise", "\$this->_reverse_prep_left('\\3', '\\1', '\\2', '\\4')", ' '.$code );
		
		$php = $this->_reverse_ipbhtml($php);
		
		return "<".$start."=\"".trim($code)."\">\n".$php."\n</if>\n";
	}
	
	//===================================================
	// Reverse: PHP else to HTML else
	//===================================================
	
	/**
	* Reverse the ELSE tag
	*
	* @param	string	PHP Data
	* @return	string	Converted Data
	*/
	function _reverse_else( $php )
	{
		$php = $this->_trim_slashes(trim($php));
		
		$php = $this->_reverse_ipbhtml($php);
		
		return "<else>\n".$php."\n</else>\n";
	}
	
	//===================================================
	// Reverse: $IPBHTML to normal $HTML
	//===================================================
	
	/**
	* Reverse HEREDOC tags
	*
	* @param	string	Raw PHP Data
	* @return	string	Converted Data
	*/
	function _reverse_ipbhtml( $code )
	{
		$code = $this->_trim_slashes($code);
		
		$code = preg_replace("/".'\$'."IPBHTML\s+?\.?=\s+?<<<EOF(.+?)EOF;\s?/si", "\\1", $code );
		
		$code = trim($code);
		$code = $this->_trim_newlines($code);
		
		return $code;
	}
	
	//===================================================
	// Reverse PHP IF code to HTML code
	//===================================================
	
	/**
	* Prepare: Reverse PHP IF code to HTML safe code
	*
	* @param	string
	* @param	string
	* @param	string
	* @param	string
	* @return	string	Converted Data
	*/
	function _reverse_prep_left($left, $andor="", $fs="", $ls="")
	{
		$left = trim($this->_trim_slashes($left));
		
		if ( preg_match( "/".'\$'."this->ipsclass->/", $left ) )
		{
			$left = preg_replace( "/".'\$'."this->ipsclass->(.+?)$/", 'ipb.'."\\1", $left );
		}
		else
		{
			$left = str_replace( '$', '', $left );
		}
		
		return $andor.$fs.$left.$ls;
	}

	//===================================================
	// Statement: Return PHP 'IF' statement
	//===================================================
	
	/**
	* Prepare: If code
	*
	* @param	string	If code
	* @param	string	HTML
	* @return	string	Converted Data
	*/
	function _statement_if( $code, $html )
	{
		$html = $this->_func_prep_html($html);
		$code = $this->_func_prep_if($code);
		
		return "\n//startif\nif ( $code )\n{\n\$IPBHTML .= <<<EOF\n$html\nEOF;\n}//endif\n";
	}
	
	//===================================================
	// Statement: Return PHP 'ELSE IF' statement
	//===================================================
	
	/**
	* Prepare: Else If code
	*
	* @param	string	Else If code
	* @param	string	HTML
	* @return	string	Converted Data
	*/
	function _statement_elseif( $code, $html )
	{
		$html = $this->_func_prep_html($html);
		$code = $this->_func_prep_if($code);
		
		return "\nelse if ( $code )\n{\n\$IPBHTML .= <<<EOF\n$html\nEOF;\n}//endif\n";
	}
	
	//===================================================
	// Statement: Return PHP 'ELSE' statement
	//===================================================
	
	/**
	* Prepare: Else code
	*
	* @param	string	HTML
	* @return	string	Converted Data
	*/
	function _statement_else( $html )
	{
		$html = $this->_func_prep_html($html);
		
		return "\n//startelse\nelse\n{\n\$IPBHTML .= <<<EOF\n$html\nEOF;\n}//endelse\n";
	}
	
	
	//===================================================
	// Strip leading newlines, etc
	//===================================================
	
	function _func_prep_html($html)
	{
		$html = trim($this->_trim_slashes($html));
		
		//$html = preg_replace( '/"/', '\\"', $html );
		
		return $html;
	}
	
	//===================================================
	// Sort out left bit of comparison
	//===================================================
	
	function _func_prep_left($left, $andor="", $fs="", $ls="")
	{
		$left = trim($this->_trim_slashes($left));
		
		if ( preg_match( "/^ipb\./", $left ) )
		{
			$left = preg_replace( "/^ipb\.(.+?)$/", '$this->ipsclass->'."\\1", $left );
		}
		else
		{
			$left = '$'.$left;
		}
		
		return $andor.$fs.$left.$ls;
	}
	
	//===================================================
	// Statement: Prep AND OR, etc
	//===================================================
	
	function _func_prep_if( $code )
	{
		$code = $this->_trim_slashes($code);
		
		$code = preg_replace( "/(^|and|or)(\s+)(.+?)(\s|$)/ise", "\$this->_func_prep_left('\\3', '\\1', '\\2', '\\4')", ' '.$code );
		
		return trim($code);
	}
	
	//===================================================
	// Remove leading and trailing newlines
	//===================================================
	
	function _trim_newlines($code)
	{
		$code = preg_replace("/^\n{1,}/s", "", $code );
		$code = preg_replace("/\n{1,}$/s", "", $code );
		return $code;
	}
	
	//===================================================
	// Remove preg_replace/e slashes
	//===================================================
	
	function _trim_slashes($code)
	{
		$code = str_replace( '\"' , '"', $code );
		$code = str_replace( "\\'", "'", $code );
		return $code;
	}

	
}





?>