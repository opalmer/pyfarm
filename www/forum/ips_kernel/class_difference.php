<?php
/*
+---------------------------------------------------------------------------
|   Invision Power Services Kernel Module
|	Invision Power Board
|   ========================================
|   by Matthew Mecham
|   (c) 2005 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
+---------------------------------------------------------------------------
|
|   > Difference Engine.
|   > Script written by Matt Mecham
|   > Date started: Thursday 10th February 2005 (10:47)
|
+---------------------------------------------------------------------------
*/

/**
* Differences class
*
* Get file differences
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
* Differences class
*
* Get file differences
*
* @package	IPS_KERNEL
* @author   Matt Mecham
* @version	2.1
*/
class class_difference
{
	# Globals
	
	/**
	* Shell command
	*
	* @var string
	*/
	var $diff_command = 'diff';
	
	/**
	* Type of diff to use
	*
	* @var integer
	*/
	var $method       = 'exec';
	
	/**
	* Differences found?
	*
	* @var integer
	*/
	var $diff_found   = 0;
	
	/**
	* Post process DIFF result?
	*
	* @var integer
	*/
	var $post_process = 1;
	
	/*-------------------------------------------------------------------------*/
	// Constructor
	/*-------------------------------------------------------------------------*/
	
	function class_difference()
	{
		//-------------------------------
		// Server?
		//-------------------------------
		
		if ( (substr(PHP_OS, 0, 3) == 'WIN') OR ( ! function_exists('exec') ) )
		{
			$this->method = 'CGI';
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Produce difference output
	// //@header("Content-type: text/plain"); print_r($diff_res_array); exit();
	/*-------------------------------------------------------------------------*/
	
	/**
	* Wrapper function to get differences
	*
	* @return	string	Diff data
	*/
	function get_differences( $str1, $str2 )
	{
		$this->diff_found = 0;
		
		$str1       = $this->_diff_tag_space($str1);
		$str2       = $this->_diff_tag_space($str2);
		$str1_lines = $this->_diff_explode_string_into_words($str1);
		$str2_lines = $this->_diff_explode_string_into_words($str2);
		
		if ( $this->method == 'CGI' )
		{
			$diff_res   = $this->_get_cgi_diff( implode( chr(10), $str1_lines ).chr(10), implode( chr(10), $str2_lines ).chr(10) );
		}
		else if ( $this->method == 'PHP' )
		{
			$diff_res   = $this->_get_php_diff( implode( chr(10), $str1_lines ).chr(10), implode( chr(10), $str2_lines ).chr(10) );
		}
		else
		{
			$diff_res   = $this->_get_exec_diff( implode( chr(10), $str1_lines ).chr(10), implode( chr(10), $str2_lines ).chr(10) );
		}
		
		//-------------------------------
		// Post process?
		//-------------------------------
		
		if ( $this->post_process )
		{
			if ( is_array($diff_res) )
			{
				reset($diff_res);
				$c              = 0;
				$diff_res_array = array();
				
				foreach( $diff_res as $l_val )
				{
					if ( intval($l_val) )
					{
						$c = intval($l_val);
						$diff_res_array[$c]['changeInfo'] = $l_val;
					}
					
					if (substr($l_val,0,1) == '<')
					{
						$diff_res_array[$c]['old'][] = substr($l_val,2);
					}
					
					if (substr($l_val,0,1) == '>')
					{
						$diff_res_array[$c]['new'][] = substr($l_val,2);
					}
				}
	
				$out_str    = '';
				$clr_buffer = '';
				
				for ( $a = -1; $a < count($str1_lines); $a++ )
				{
					if ( is_array( $diff_res_array[$a+1] ) )
					{
						if ( strstr( $diff_res_array[$a+1]['changeInfo'], 'a') )
						{
							$this->diff_found = 1;
							$clr_buffer .= htmlspecialchars($str1_lines[$a]).' ';
						}
	
						$out_str     .= $clr_buffer;
						$clr_buffer   = '';
						
						if (is_array($diff_res_array[$a+1]['old']))
						{
							$this->diff_found = 1;
							$out_str.='<del style="-ips-match:1">'.htmlspecialchars(implode(' ',$diff_res_array[$a+1]['old'])).'</del> ';
						}
						
						if (is_array($diff_res_array[$a+1]['new']))
						{
							$this->diff_found = 1;
							$out_str.='<ins style="-ips-match:1">'.htmlspecialchars(implode(' ',$diff_res_array[$a+1]['new'])).'</ins> ';
						}
						
						$cip = explode(',',$diff_res_array[$a+1]['changeInfo']);
						
						if ( ! strcmp( $cip[0], $a + 1 ) )
						{
							$new_line = intval($cip[1])-1;
							
							if ( $new_line > $a )
							{
								$a = $new_line;
							}
						}
					} 
					else
					{
						$clr_buffer .= htmlspecialchars($str1_lines[$a]).' ';
					}
				}
				
				$out_str .= $clr_buffer;
	
				$out_str  = str_replace('  ',chr(10),$out_str);
				
				$out_str  = $this->_diff_tag_space($out_str,1);
				
				return $out_str;
			}
		}
		else
		{
			return $diff_res;
		}
	}

	/*-------------------------------------------------------------------------*/
	// Adds space character after HTML tags
	/*-------------------------------------------------------------------------*/
	
	/**
	* Adds space character after HTML tags
	*
	* @return	string	Converted string
	*/
	function _diff_tag_space( $str, $rev=0 )
	{
		if ( $rev )
		{
			return str_replace(' &lt;','&lt;',str_replace('&gt; ','&gt;',$str) );
		}
		else
		{
			return str_replace('<',' <',str_replace('>','> ',$str) );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Explodes input string into words
	/*-------------------------------------------------------------------------*/
	
	/**
	* Explodes input string into words
	*
	* @return	array
	*/
	function _diff_explode_string_into_words( $str )
	{ 
		$str_array = $this->_explode_trim( chr(10), $str );
		$out_array = array();

		reset($str_array);
		
		foreach( $str_array as $low )
		{
			$all_words   = $this->_explode_trim( ' ', $low, 1 );
			$out_array   = array_merge($out_array, $all_words);
			$out_array[] = '';
			$out_array[] = '';
		}
		
		return $out_array;
	}
	
	/*-------------------------------------------------------------------------*/
	// Explode into array and trim
	/*-------------------------------------------------------------------------*/
	
	/**
	* Explode into array and trim
	*
	* @return	array
	*/
	function _explode_trim( $delim, $str, $remove_blank=0 )
	{
		$tmp   = explode( $delim, trim($str) );
		$final = array();
	
		foreach( $tmp as $i )
		{
			if ( $remove_blank AND ( $i === '' OR $i === NULL ) ) //!$i AND $i !== 0 )
			{
				continue;
			}
			else
			{
				$final[] = trim($i);
			}
		}

		return $final;
	}
	
	/*-------------------------------------------------------------------------*/
	// Produce differences using PHP
	/*-------------------------------------------------------------------------*/
	
	/**
	* Produce differences using PHP
	*
	* @param	string	comapre string 1
	* @param	string	comapre string 2
	* @return	string
	*/
    function _get_php_diff( $str1 , $str2 )
    {
    	$str1 = explode( "\n", str_replace( "\r\n", "\n", $str1 ) );
    	$str2 = explode( "\n", str_replace( "\r\n", "\n", $str2 ) );
    	
    	include_once KERNEL_PATH.'PEAR/Text/Diff.php';
		include_once KERNEL_PATH.'PEAR/Text/Diff/Renderer.php';
		include_once KERNEL_PATH.'PEAR/Text/Diff/Renderer/inline.php';
		
		$diff = new Text_Diff( $str1,$str2 );

		$renderer = new Text_Diff_Renderer_inline();
		
		$result   = $renderer->render($diff);
		
		# Inline formatting adjustments
		$result = htmlspecialchars( $result );
		
		$result = str_replace( "&lt;ins&gt;", '<ins style="-ips-match:1">', $result );
		$result = str_replace( "&lt;del&gt;", '<del style="-ips-match:1">', $result );
		$result = preg_replace( "#&lt;/(ins|del)&gt;#", "</\\1>", $result );
		
		# Got a match?
		if ( strstr( $result, 'style="-ips-match:1"' ) )
		{
			$this->diff_found = 1;
		}
		
		# No post processing please
		$this->post_process = 0;
		
		# Convert lines to a space, and two spaces to a single line
		$result = str_replace('  ', chr(10), str_replace( "\n", " ", $result ) );
		$result = $this->_diff_tag_space($result,1);
		
		return $result;
    }

	/*-------------------------------------------------------------------------*/
	// Produce differences using unix
	/*-------------------------------------------------------------------------*/
	
	/**
	* Produce differences using unix
	*
	* @return	string
	*/
	function _get_exec_diff( $str1, $str2 )
	{
		//-------------------------------
		// Write the tmp files
		//-------------------------------
		
		$file1 = ROOT_PATH.'uploads/'.time().'-1';
		$file2 = ROOT_PATH.'uploads/'.time().'-2';
		
		if ( $FH1 = @fopen( $file1, 'w' ) )
		{
			@fwrite( $FH1, $str1, strlen($str1) );
			@fclose( $FH1 );
		}
		
		if ( $FH2 = @fopen( $file2, 'w' ) )
		{
			@fwrite( $FH2, $str2, strlen($str2) );
			@fclose( $FH2 );
		}
		
		//-------------------------------
		// Check
		//-------------------------------
		
		if ( file_exists( $file1 ) and file_exists( $file2 ) )
		{
			exec( $this->diff_command.' '.$file1.' '.$file2, $result );
			
			@unlink( $file1 );
			@unlink( $file2 );
			
			return $result;
		}
		else
		{
			return "Error, files not written to disk";
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Produce differences using unix
	/*-------------------------------------------------------------------------*/
	
	/**
	* Produce differences using CGI
	*
	* @return	string
	*/
	function _get_cgi_diff( $str1, $str2 )
	{
		//-----------------------------------------
		// Load file management class
		//-----------------------------------------
		
		require_once( IPS_CLASSES_PATH.'/class_file_management.php' );
		$this->class_file_management = new class_file_management();
		$this->class_file_management->use_sockets = $this->ipsclass->vars['enable_sockets'];
		
		//-------------------------------
		// Write the tmp files
		//-------------------------------
		
		$file1 = 'tmp-1';
		$file2 = 'tmp-2';
		
		if ( $FH1 = @fopen( ROOT_PATH.'uploads/'.$file1, 'w' ) )
		{
			@fwrite( $FH1, $str1, strlen($str1) );
			@fclose( $FH1 );
		}
		
		if ( $FH2 = @fopen( ROOT_PATH.'uploads/'.$file2, 'w' ) )
		{
			@fwrite( $FH2, $str2, strlen($str2) );
			@fclose( $FH2 );
		}
		
		//-------------------------------
		// Check
		//-------------------------------
		
		if ( file_exists( ROOT_PATH.'uploads/'.$file1 ) AND file_exists( ROOT_PATH.'uploads/'.$file2 ) )
		{
			$result = $this->class_file_management->get_file_contents( $this->ipsclass->vars['base_url']."/".CP_DIRECTORY."/".IPS_CGI_DIRECTORY."/cgi_getdifference.cgi" );
			
			@unlink( ROOT_PATH.'uploads/'.$file1 );
			@unlink( ROOT_PATH.'uploads/'.$file2 );
			
			return explode( "\n", $result );
		}
		else
		{
			return "Error, files not written to disk";
		}
	}
	
}
?>