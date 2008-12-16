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
|   > Template Engine module NEW (KERNEL)
|   > Module written by Matt Mecham
|   > Date started: Wednesday 23rd February 13:53
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
|   New template module to build, rebuild and generate caches of templates
|   which include the new IPB HTML Logic system.
|   Example:
|   
|    <if='ibf.vars['threaded_per_page'] == 10'>
|       html here
||    <else />
|       html here
|    </if>
|   
+--------------------------------------------------------------------------
*/

define( 'IPS_TEMPLATE_DEBUG'     , 0 );
define( 'IPS_TEMPLATE_DEBUG_FILE', dirname( __FILE__ ) . '/../cache/template_debug.cgi' );

/**
* Template Engine (NOT USED CURRENTLY: FOR IPB 3.0, IP.Dynamic)
*
* Methods and functions for converting HTML logic into PHP code
*
* @package		IPS_KERNEL
* @author		Matt Mecham
* @copyright	Invision Power Services, Inc.
* @version		1.0
* @ignore
*/

/**
*
*/

/**
* Template Engine (NOT USED CURRENTLY: FOR IPB 3.0, IP.Dynamic)
*
* Methods and functions for converting HTML logic into PHP code
*
* @package	IPS_KERNEL
* @author   Matt Mecham
* @version	1.0
* @ignore
*/
class class_template
{
	# The basics
	var $root_path   = './';
	var $cache_dir   = 'skin_cache';
	var $cache_id    = '1';
	var $database_id = '1';
	
	var $cache_path  = '';
	
	/**
	* Foreach function blocks
	* Holds the foreach code
	*
	* @var array
	*/
	var $foreach_blocks = array();
	
	/**
	* Allow PHP code to execute
	*/
	var $allow_php_code = 1;
	
	
	function class_template()
	{
		$this->cache_path = $this->root_path . $this->cache_dir . '/cacheid_' . $this->cache_id;
	}
	
	/*-------------------------------------------------------------------------*/
	// Eval on the fly
	/*-------------------------------------------------------------------------*/
	
	/**
	* convert_cache_to_eval
	*
	* Takes raw PHP data (from a MASTEr cache class) and formats it ready
	* for eval'ing in the scripts.
	*
	* @param	$data	string	The actual contents of the class, php tags 'an all.
	*/
	function convert_cache_to_eval($data='', $full_class_name='', $id=1)
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$final_content = $this->_return_function_data( $data );
		
		//-----------------------------------------
		// Grab "linked" template bits
		//-----------------------------------------
		
		/*
		<__templatebits__>
			<bit>skin_global:personal_portal_gender_image</bit>
			<bit>skin_global:personal_portal_online_image</bit>
		</__templatebits__>
		*/
		
		if ( strstr( $data, '<__templatebits__>' ) )
		{
			$_templates = array();
			
			preg_match( "#<__templatebits__>(.+?)</__templatebits__>#is", $data, $match );
			
			if ( $match[1] )
			{ 
				preg_match_all( "#<bit>(.+?)</bit>#is", $data, $matches );
				
				for ( $i = 0; $i < count( $matches[0] ); $i++)
				{
					list( $file, $name ) = explode( ':', $matches[1][$i] );
					
					if ( $file AND $name )
					{
						$_templates[ $file ][] = $name;
					}
				}
			}
			
			//-----------------------------------------
			// Got stuff to load?
			//-----------------------------------------
			
			if ( is_array( $_templates ) AND count( $_templates ) )
			{
				foreach( $_templates as $file => $idx )
				{
					$_data = implode( '', file( CACHE_PATH."cache/skin_cache/cacheid_".$id."/".$file.".php" ) );
					
					$final_content .= $this->_return_function_data( $_data, $_templates[ $file ] );
				}
			}
		}
			
		//-----------------------------------------
		// Return the stuff...
		//-----------------------------------------
		
		$out  = "class {$full_class_name} {\n\n";
		$out .= "\t".'var $ipsclass;'."\n";
		$out .= $final_content;
		$out .= "\n\n}";
		
		return $out;
	}
	
	/**
	* _return_function_data
	*
	* Extracts function data
	*
	* @param	$data	string	The actual contents of the class, php tags 'an all.
	*/
	function _return_function_data( $data='', $function_find=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$final_content = '';
		
		//-----------------------------------------
		// Remove PHP tags
		//-----------------------------------------

		$data = preg_replace( "#<"."\?php\n+?(.+?)\n+?\?".">#is", "\\1", $data );

		//-----------------------------------------
		// Pick through and get all function names
		//-----------------------------------------
		
		$data = str_replace( "\r"  , "\n", $data );
		$data = str_replace( "\n\n", "\n", $data );
							
		//-----------------------------------------
		// Continue...
		//-----------------------------------------
		
		$farray = explode( "\n", $data );
		
		//-----------------------------------------
		// Functions...
		//-----------------------------------------
		
		$functions    = array();
		$script_token = 0;
		$flag         = 0;
		
		foreach( $farray as $f )
		{
			//-----------------------------------------
			// Skip javascript functions...
			//-----------------------------------------
			
			if ( preg_match( "/<script/i", $f ) )
			{
				$script_token = 1;
			}
			
			if ( preg_match( "/<\/script>/i", $f ) )
			{
				$script_token = 0;
			}
			
			//-----------------------------------------
			// NOT IN JS
			//-----------------------------------------
			
			if ( $script_token == 0 )
			{
				if ( preg_match( "/^function\s*([\w\_]+)\s*\((.*)\)/i", $f, $matches ) )
				{
					$functions[ $matches[1] ] = '';
					$config[ $matches[1] ]    = $matches[2];
					$flag                     = $matches[1];
					continue;
				}
			}
			
			if ( $flag )
			{
				$functions[ $flag ] .= $f."\n";
				continue;
			}
		}

		//-----------------------------------------
		// Build the file...
		//-----------------------------------------
		
		foreach( $functions as $fname => $ftext )
		{
			if ( is_array( $function_find ) AND count( $function_find ) )
			{
				if ( ! in_array( $fname, $function_find ) )
				{
					continue;
				}
			}
			
			preg_match( "/".'\$'."IPBHTML\s+?\.?=\s+?<<<EOF(.+?)EOF;\s?/si", $ftext, $matches );
			
			$func_data      = trim( $config[$fname] );
			$final_content .= $this->convert_html_to_php( $fname, $func_data, $matches[1] );
		}
		
		//-----------------------------------------
		// Return...
		//-----------------------------------------
		
		return $final_content;
	}
	
	/*-------------------------------------------------------------------------*/
	// Convert HTML to PHP cache file
	/*-------------------------------------------------------------------------*/
	
	function convert_html_to_php($func_name, $func_data, $func_html, $func_desc="", $com_bit_update_trigger='', $compile=1)
	{
		//-------------------------------
		// INIT
		//-------------------------------
		
		$this->foreach_blocks = array();
		
		//-----------------------------------------
		// Debug?
		//-----------------------------------------
		
		$this->_add_debug( "Preparing to convert: $func_name" . "\n" . $func_html );
		
		//-------------------------------
		// Make sure we have ="" on each
		// func data
		//-------------------------------
		
		$func_data = preg_replace( "#".'\$'."(\w+)(,|$)#i", "\$\\1=\"\"\\2", str_replace( " ", "", $func_data ) );
		
		if ( $compile )
		{
			$_func_code = '"' . $this->compile_html_to_php( trim( $func_html ), $func_data ) . '"';
		}
		else
		{
			$_func_code = "<<<EOF\n" . $this->unconvert_tags( $func_html ) . "\nEOF";
		}
		
		//-----------------------------------------
		// Make safe.. clear up any potential escapes
		//-----------------------------------------
		
		$_func_code = preg_replace( "#\\\{1,}\"#s", '\\"', $_func_code );
		
		//-----------------------------------------
		// Continue...
		//-----------------------------------------
		
		$top    = "//===========================================================================\n".
			      "// <ips:{$func_name}:desc:{$func_desc}:trigger:{$com_bit_update_trigger}>\n".
			      "//===========================================================================\n";
			      
		$start  = "function {$func_name}($func_data) {\n\$IPBHTML = \"\";\n//--starthtml--//\n";
		$middle = '$IPBHTML .= '.$_func_code.';';
		
		$end    = "\n//--endhtml--//\nreturn \$IPBHTML;\n}\n";
		
		//-------------------------------
		// Add foreach blocks...
		//-------------------------------
		
		if ( count( $this->foreach_blocks ) )
		{
			$end .= "\n\n" . implode( "\n\n", $this->foreach_blocks ) . "\n";
			
			//----------------------------------------
			// Check embedded foreach blocks
			//----------------------------------------
			
			if ( strstr( $end, "<xxforeach" ) )
			{
				$end = preg_replace( "#<xxforeach_([^>]+?)xx>(.+?)</xxforeach_\\1xx>#si", "\".\\2.\"", $end );
			}
			
			//-----------------------------------------
			// Remove raw PHP tags
			//-----------------------------------------

			$end = preg_replace( "#<php>(.+?)</php>#si", "", $end );
		}
		
		//-----------------------------------------
		// Sort out the rest of the PHP tags
		//-----------------------------------------
		
		$php_tags = $this->_process_raw_php_tags( $_func_code );
		
		//-----------------------------------------
		// Remove raw PHP tags
		//-----------------------------------------
		
		$middle = preg_replace( "#<php>(.+?)</php>#si", "", $middle );
		
		//-----------------------------------------
		// Debug?
		//-----------------------------------------
		
		$this->_add_debug( "FINISHED: $func_name" . "\n" . $top.$start.$php_tags.$middle.$end );
		
		//-------------------------------
		// Return
		//-------------------------------
		
		return $top.$start.$php_tags.$middle.$end;
	}
	
	/*-------------------------------------------------------------------------*/
	// Convert HTML logic to cached PHP
	/*-------------------------------------------------------------------------*/
	
	function compile_html_to_php( $text, $normal_func_data='' )
	{
		//----------------------------------------
		// INIT
		//----------------------------------------
		
		$do_foreach = 0;
		$do_if      = 0;
		
		//----------------------------------------
		// First pass...
		//----------------------------------------
		
		if ( strstr( $text, "<foreach=" ) )
		{
			$do_foreach = 1;
		}
		
		//----------------------------------------
		// Second pass...
		//----------------------------------------
		
		if ( strstr( $text, "<if=" ) )
		{
			$do_if = 1;
		}
		
		//----------------------------------------
		// Add slashes if required
		//----------------------------------------
		
		if ( $do_if OR $do_foreach )
		{
			$text = addslashes( $text );
		}
		else
		{
			return str_replace( '"', '\\"', $text );
		}
		
		//----------------------------------------
		// HTML FOREACH logic...
		//----------------------------------------
			
		if ( $do_foreach )
		{
			$text = $this->_process_raw_html_foreach_logic( $text, $normal_func_data );
		}
		
		//----------------------------------------
		// HTML IF/ELSE logic...
		//----------------------------------------
			
		if ( $do_if )
		{
			$text = $this->_process_raw_html_logic( $text );
		}
		
		//----------------------------------------
		// Last pass...
		//----------------------------------------
		
		if ( $do_foreach )
		{
			if ( strstr( $text, "<xxforeach" ) )
			{
				$text = preg_replace( "#<xxforeach_([^>]+?)xx>(.+?)</xxforeach_\\1xx>#si", "\".\\2.\"", $text );
			}
		}
		
		//----------------------------------------
		// Make code OK
		//----------------------------------------
		
		if ( $do_if OR $do_foreach )
		{
			$text = str_replace('\\\\$', '\\$', $text);
		}

		return $text;
	}
	
	/*-------------------------------------------------------------------------*/
	// Convert HTML logic to cached PHP (work function)
	/*-------------------------------------------------------------------------*/
	
	function _process_raw_html_foreach_logic( $text, $normal_func_data='' )
	{
		//----------------------------------------
		// INIT
		//----------------------------------------
		
		$total_length = strlen( $text );
		$template     = $text;
		$statement    = "";
		$arg_true     = "";
		$arg_false    = "";
		
		# Tag specifics
		$tag_foreach       = '<foreach=';
		$found_foreach     = -1;
		$tag_end_foreach   = '</foreach>';
		$found_end_foreach = -1;
		
		$allow_delim  = array( '"', '\'' );
		
		$_tmp_func_data    = explode( ",", $normal_func_data );
		$_final            = array();
		$clean_func_data   = '';
		
		//----------------------------------------
		// Get function arguments
		//----------------------------------------
		
		foreach( $_tmp_func_data as $_i )
		{
			preg_match( "#".'\$'."(\w+)(=|,|$)#i", $_i, $match );
			$_final[] = '$'.$match[1];
		}
		
		$clean_func_data = implode( ",", $_final );
		
		//----------------------------------------
		// Keep the server busy for a while
		//----------------------------------------
		
		while ( 1 == 1 )
		{
			$_end = 0;
			
			//----------------------------------------
			// Look for opening <if tag...
			//----------------------------------------
			
			$found_foreach = strpos( $template, $tag_foreach, $found_end_foreach + 1 );
			
			//----------------------------------------
			// No logic found? 
			//----------------------------------------
			
			if ( $found_foreach === FALSE )
			{
				break;
			}
			
			//----------------------------------------
			// Beginning of the logic...
			//----------------------------------------
			
			$_start = $found_foreach + strlen($tag_foreach) + 2;
			
			$delim  = $template[ $_start - 1 ];
			
			//----------------------------------------
			// Make sure we have statement wrapped in
			// either ' or "
			//----------------------------------------
			
			if ( ! in_array( $delim, $allow_delim ) )
			{
				$found_end_foreach = $found_foreach + 1;
				continue;
			}
			
			//----------------------------------------
			// End statement?
			//----------------------------------------
			
			$found_end_foreach = strpos($template, $tag_end_foreach, $_end + 3);
			
			//----------------------------------------
			// No end statement found
			//----------------------------------------
			
			if ( $found_end_foreach === FALSE )
			{ 
				return str_replace("\\'", '\'', $template);
			}
			
			//----------------------------------------
			// Find end of statement
			//----------------------------------------
			
			for ( $i = $_start; $i < $total_length; $i++ )
			{
				if ( $template[ $i ] == $delim AND $template[$i - 2] != '\\' AND $template[$i + 1] == '>' )
				{
					//----------------------------------------
					// Unescaped end delimiter
					//----------------------------------------
					
					$_end = $i - 1;
					break;
				}
			}
			
			//----------------------------------------
			// No end statement found
			//----------------------------------------
			
			if ( ! $_end )
			{
				return str_replace("\\'", '\'', $template);
			}
			
			//----------------------------------------
			// Get statement
			//----------------------------------------
			
			$statement = $this->unconvert_tags( substr( $template, $_start, $_end - $_start ) );
			
			//----------------------------------------
			// Not got?
			//----------------------------------------
			
			if ( empty($statement) )
			{
				$found_end_foreach = $found_foreach + 1;
				continue;
			}
			
			//----------------------------------------
			// No closing > on logic?
			//----------------------------------------
	
			if ( $template[$_end + 2] != '>' )
			{
				$found_end_foreach = $found_foreach + 1;
				continue;
			}
	
			//----------------------------------------
			// Check recurse
			//----------------------------------------
			
			$if_found_recurse = $found_foreach;
			
			while ( 1 == 1 )
			{
				//----------------------------------------
				// Got an IF?
				//----------------------------------------
				
				$if_found_recurse = strpos( $template, $tag_foreach, $if_found_recurse + 1 );
				
				//----------------------------------------
				// None found...
				//----------------------------------------
				
				if ( $if_found_recurse === FALSE OR $if_found_recurse >= $found_end_foreach )
				{
					break;
				}
				
				$if_end_recurse      = $found_end_foreach;
				$found_end_foreach   = strpos( $template, $tag_end_foreach, $if_end_recurse + 1 );
				
				//----------------------------------------
				// None found...
				//----------------------------------------
				
				if ( $found_end_foreach === FALSE )
				{
					return str_replace("\\'", "'", $template);
				}
			}
			
			
			$rlen   = $found_end_foreach - strlen($tag_end_foreach) + 1 - $_end + 1;
			$block  = substr($template, $_end + 3, $rlen + 5);
			
			//----------------------------------------
			// Recurse
			//----------------------------------------
			
			if ( strpos( $block, $tag_foreach ) !== FALSE )
			{
				//----------------------------------------
				// Add in any extra new vars...
				//----------------------------------------
				
				$_normal_func_data = $normal_func_data;
				
				if ( strstr( strtolower($statement), 'as' ) )
				{
					# Get the last part of the argument
					list( $_trash, $keep ) = explode( 'as', $statement );
					$keep = trim($keep);
					
					if ( strstr( $keep, '=>' ) )
					{
						list( $one, $two ) = explode( '=>', $keep );
						$one = trim( $one );
						$two = trim( $two );
						
						$_normal_func_data .= ",{$one}='',{$two}=''";
					}
					else
					{
						$_normal_func_data .= ",{$keep}=''";
					}
				}
				
				$block = $this->_process_raw_html_foreach_logic($block, $_normal_func_data);
			}
			
			//----------------------------------------
			// Clean up...
			//----------------------------------------
			
			$str_find    = array('\\"', '\\\\');
			$str_replace = array('"'  , '\\'  );
			
			$str_find[]    = "\\'";
			$str_replace[] = "'";
			
			$str_find[]    = '\\$delim';
			$str_replace[] =  $delim;
			
			//----------------------------------------
			// ...statement
			//----------------------------------------
			
			$statement = str_replace($str_find, $str_replace, $statement);
			$block     = str_replace($str_find, $str_replace, $block);
			
			//----------------------------------------
			// Create PHP statement
			//----------------------------------------
			
			$function_name = '__foreach_loop__'. uniqid( 'id_' );
			$php_statement = '<xxforeach_'.$function_name.'xx>$this->'.$function_name.'('.$clean_func_data.')</xxforeach_'.$function_name.'xx>';
			$php_block     = $this->_process_raw_html_logic( addslashes($block) );
			$php_tags      = $this->_process_raw_php_tags( $block );
			
			
			$this->foreach_blocks[] = <<<EOF
//===========================================================================
// Foreach loop function
//===========================================================================

function {$function_name}($normal_func_data)
{
	\$_ips___x_retval = '';
	
	foreach( $statement )
	{
		{$php_tags}
		\$_ips___x_retval .= "{$php_block}";
	}
	
	return \$_ips___x_retval;
}
EOF;
			$template = substr_replace( $template, $php_statement, $found_foreach, $found_end_foreach + strlen($tag_end_foreach) - $found_foreach);
			
			$found_end_foreach = $found_foreach + strlen($php_statement) - 1;
		}
	
		return str_replace("\\'", "'", $template);
	}
	
	/*-------------------------------------------------------------------------*/
	// Convert HTML PHP tags
	/*-------------------------------------------------------------------------*/
	
	function _process_raw_php_tags( $text )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$php = "";
		
		//-----------------------------------------
		// Allow PHP code in templates?
		//-----------------------------------------
		
		if ( ! $this->allow_php_code )
		{
			return '';
		}
		
		//-----------------------------------------
		// EXTRACT!
		//-----------------------------------------
		
		preg_match_all( "#<php>(.+?)</php>#si", $text, $match );
		
		for ( $i = 0; $i < count($match[0]); $i++ )
		{
			$php_code     = trim( $match[1][$i] );
			$complete_tag = $match[0][$i];
			
			$str_find    = array('\\"', '\\\\');
			$str_replace = array('"'  , '\\'  );
			
			$str_find[]    = "\\'";
			$str_replace[] = "'";
			
			$php_code = str_replace($str_find, $str_replace, $php_code);
			
			$php .= "\n".$php_code."\n";
		}
		
		return $php;
	}
	
	/*-------------------------------------------------------------------------*/
	// Convert HTML logic to cached PHP (work function)
	/*-------------------------------------------------------------------------*/
	
	function _process_raw_html_logic( $text )
	{
		//----------------------------------------
		// INIT
		//----------------------------------------
		
		$total_length = strlen( $text );
		$template     = $text;
		$statement    = "";
		$arg_true     = "";
		$arg_false    = "";
		
		# Tag specifics
		$tag_if       = '<if=';
		$found_if     = -1;
		$tag_end_if   = '</if>';
		$found_end_if = -1;
		$tag_else     = '<else />';
		$found_else   = -1;
		
		$allow_delim  = array( '"', '\'' );
		
		//----------------------------------------
		// Keep the server busy for a while
		//----------------------------------------
		
		while ( 1 )
		{
			//-----------------------------------------
			// Update template length
			//-----------------------------------------
			
			$total_length = strlen( $template );
			
			$_end = 0;
			
			//----------------------------------------
			// Look for opening <if tag...
			//----------------------------------------
			
			$found_if = strpos( $template, $tag_if, $found_end_if + 1 );
			
			#$this->_add_debug( "Found <if>: $found_if" );
			
			//----------------------------------------
			// No logic found? 
			//----------------------------------------
			
			if ( $found_if === FALSE )
			{
				break;
			}
			
			//----------------------------------------
			// Beginning of the logic...
			//----------------------------------------
			
			$_start = $found_if + strlen($tag_if) + 2;
			
			$delim  = $template[ $_start - 1 ];
			
			//----------------------------------------
			// Make sure we have statement wrapped in
			// either ' or "
			//----------------------------------------
			
			if ( ! in_array( $delim, $allow_delim ) )
			{
				$found_end_if = $found_if + 1;
				continue;
			}
			
			//----------------------------------------
			// End statement?
			//----------------------------------------
			
			$found_end_if = strpos($template, $tag_end_if, $_start + 3);
			
			#$this->_add_debug( "Found </if>: $found_end_if" );
			
			//----------------------------------------
			// No end statement found
			//----------------------------------------
			
			if ( $found_end_if === FALSE )
			{ 
				return str_replace("\\'", '\'', $template);
			}
			
			//----------------------------------------
			// Find end of statement
			//----------------------------------------
			
			for ( $i = $_start; $i < $total_length; $i++ )
			{
				if ( $template[ $i ] == $delim AND $template[$i - 2] != '\\' AND $template[$i + 1] == '>' )
				{
					//----------------------------------------
					// Unescaped end delimiter
					//----------------------------------------
					
					$_end = $i - 1;
					break;
				}
			}
			
			//----------------------------------------
			// No end statement found
			//----------------------------------------
			
			if ( ! $_end )
			{
				return str_replace("\\'", '\'', $template);
			}
			
			//----------------------------------------
			// Get statement
			//----------------------------------------
			
			$statement = $this->unconvert_tags( substr( $template, $_start, $_end - $_start ) );
			
			//----------------------------------------
			// Not got?
			//----------------------------------------
			
			if ( empty($statement) )
			{
				$found_end_if = $found_if + 1;
				continue;
			}
			
			//----------------------------------------
			// No closing > on logic?
			//----------------------------------------
	
			if ( $template[$_end + 2] != '>' )
			{
				$found_end_if = $found_if + 1;
				continue;
			}
	
			//----------------------------------------
			// Check recurse
			//----------------------------------------
			
			$if_found_recurse = $found_if;
			
			while ( 1 )
			{
				//----------------------------------------
				// Got an IF?
				//----------------------------------------
				
				$if_found_recurse = strpos( $template, $tag_if, $if_found_recurse + 1 );
				
				#$this->_add_debug( "Found recurse <if>: $if_found_recurse" );
				
				//----------------------------------------
				// None found...
				//----------------------------------------
				
				if ( $if_found_recurse === FALSE OR $if_found_recurse >= $found_end_if )
				{
					break;
				}
				
				$if_end_recurse = $found_end_if;
				$found_end_if   = strpos( $template, $tag_end_if, $if_end_recurse + 1 );
				
				//----------------------------------------
				// None found...
				//----------------------------------------
				
				#$this->_add_debug( "Found recurse </if>: $found_end_if" );
				
				if ( $found_end_if === FALSE )
				{
					return str_replace("\\'", "'", $template);
				}
			}
	
			$found_else = strpos($template, $tag_else, $_end + 3);
	
			//----------------------------------------
			// Handle the else tags
			//----------------------------------------
			
			while ( 1 )
			{
				//----------------------------------------
				// None found...
				//----------------------------------------
				
				if ( $found_else === FALSE OR $found_else >= $found_end_if )
				{
					$found_else = -1;
					break;
				}
	
				$tmp = substr($template, $_end + 3, $found_else - $_end + 3);
				
				//----------------------------------------
				// IF tag opened
				//----------------------------------------
				
				$opened_if = substr_count($tmp, $tag_if);
				
				//----------------------------------------
				// IF closed
				//----------------------------------------
				
				$closed_if = substr_count($tmp, $tag_end_if);
				
				if ( $opened_if == $closed_if )
				{
					break;
				}
				else
				{
					$found_else = strpos($template, $tag_else, $found_else + 1);
				}
			}
			
			//----------------------------------------
			// No else
			//----------------------------------------
			
			if ( $found_else == -1 )
			{ 
				$rlen   = $found_end_if - strlen($tag_end_if) + 1 - $_end + 1;
				$_true  = substr($template, $_end + 3, $rlen);
				$_false = '';
			}
			else
			{
				$rlen   = $found_else - $_end - 3;
				$_true  = substr($template, $_end + 3, $rlen);
	
				$rlen   = $found_end_if - strlen($tag_end_if) - $found_else - 3;
				$_false = substr($template, $found_else + strlen($tag_else), $rlen);
			}
			
			//----------------------------------------
			// Recurse
			//----------------------------------------
			
			if ( strpos( $_true, $tag_if ) !== FALSE )
			{
				$_true = $this->_process_raw_html_logic($_true);
			}
			if ( strpos( $_false, $tag_if ) !== FALSE )
			{
				$_false = $this->_process_raw_html_logic($_false);
			}
	
			//----------------------------------------
			// Clean up...
			//----------------------------------------
			
			$str_find    = array('\\"', '\\\\');
			$str_replace = array('"'  , '\\'  );
			
			if ( $delim == "'" )
			{
				$str_find[]    = "\\'";
				$str_replace[] = "'";
			}
	
			$str_find[]    = '\\$delim';
			$str_replace[] =  $delim;
			
			//----------------------------------------
			// ...statement
			//----------------------------------------
			
			$statement = str_replace($str_find, $str_replace, $statement);
			
			//----------------------------------------
			// Create PHP statement
			//----------------------------------------
			
			$php_statement = "\" . (($statement) ? (\"$_true\") : (\"$_false\")) . \"";
			
			$template = substr_replace( $template, $php_statement, $found_if, $found_end_if + strlen($tag_end_if) - $found_if);
			
			$found_end_if = $found_if + strlen($php_statement) - 1;
		}
	
		return str_replace("\\'", "'", $template);
	}
	
	
	//===================================================
	// Convert special tags into HTML safe versions
	//===================================================
	
	function convert_tags($t="")
	{
		# IPB 2.1+ Kernel
		//$t = preg_replace( "/{?\\\$this->ipsclass->base_url}?/"       , "{ipb.script_url}" , $t );
		//$t = preg_replace( "/{?\\\$this->ipsclass->session_id}?/"     , "{ipb.session_id}" , $t );
		//$t = preg_replace( "#\\\$this->ipsclass->(member|vars|skin|lang|input)#i" , "ipb.\\1", $t );
		
		//----------------------------------------
		// Make some tags safe..
		//----------------------------------------
		
		$t = preg_replace( "/\{ipb\.vars\[(['\"])?(sql_driver|sql_host|sql_database|sql_pass|sql_user|sql_port|sql_tbl_prefix|smtp_host|smtp_port|smtp_user|smtp_pass|html_dir|base_dir|upload_dir)(['\"])?\]\}/", "" , $t );
				
		return $t;
	}
	
	//===================================================
	// Uncovert them back again
	//===================================================
	
	function unconvert_tags($t="")
	{
		//----------------------------------------
		// Make some tags safe..
		//----------------------------------------
		
		$t = preg_replace( "/\{ips\.vars\[(['\"])?(sql_driver|sql_host|sql_database|sql_pass|sql_user|sql_port|sql_tbl_prefix|smtp_host|smtp_port|smtp_user|smtp_pass|html_dir|base_dir|upload_dir)(['\"])?\]\}/", "" , $t );
		
		# IPB 2.1+ Kernel
		$t = preg_replace( "/{ip(s|b|d)\.script_url}/i"           , '{$this->ipsclass->base_url}'  , $t);
		$t = preg_replace( "/{ip(s|b|d)\.session_id}/i"           , '{$this->ipsclass->session_id}', $t);
		$t = preg_replace( "#ip(?:s|b|d)\.(member|vars|skin|lang|input)#i", '$this->ipsclass->\\1'         , $t );
		
		return $t;
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
	function convert_php_to_html($php)
	{
		$php = $this->_reverse_ipshtml( $this->convert_tags( $php ) );
		
		return $php;
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
	function _reverse_ipshtml( $code )
	{
		$code = $this->_trim_slashes($code);
		
		$code = preg_replace("/".'\$'."IPBHTML\s+?\.?=\s+?<<<EOF(.+?)EOF;\s?/si", "\\1", $code );
		
		$code = trim($code);
		$code = $this->_trim_newlines($code);
		
		return $code;
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
	
	/**
	* Add debug message
	*/
	function _add_debug( $msg )
	{
		if ( IPS_TEMPLATE_DEBUG AND IPS_TEMPLATE_DEBUG_FILE )
		{
			$full_msg = "==================================================================\n"
					   . "Date: " . gmdate( 'r' ) . ' - ' . $_SERVER['REMOTE_ADDR'] . "\n"
					   . $msg . "\n"
					   . "==================================================================\n";
			
			if ( $FH = @fopen( IPS_TEMPLATE_DEBUG_FILE, 'a+' ) )
			{
				fwrite( $FH, $full_msg, strlen( $full_msg ) );
				fclose( $FH );
			}
			
			return TRUE;
		}
	}

	
}





?>