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
|   > $Date: 2007-10-17 16:29:37 -0400 (Wed, 17 Oct 2007) $
|   > $Revision: 1133 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > BB Code Core Module
|   > Module written by Matt Mecham
|   > Date started: Wednesday 9th March 2005 11:27
|
+--------------------------------------------------------------------------
*/

/**
* BBCode Parsing Core Class
*
* This class contains all the main class functions
* EXAMPLE USAGE
* <code>
* $parser           =  new parse_bbcode();
* $parser->ipsclass =& $this->ipsclass;
* 
* # If you wish convert posted text into BBCode
* $parser->parse_smilies = 1;
* $parser->parse_bbcode  = 1;
* 
* $bbcode_text = $parser->pre_db_parse( $_POST['text'] );
* 
* # If you wish to display this parsed BBCode, we've still got
* # to parse HTML (where allowed) and parse user defined BBCode.
* $parser->parse_html    = 0;
* $parser->parse_nl2br   = 1;
* $ready_to_print        = $parser->pre_display_parse(  $bbcode_text  );
* 
* # Sometimes, you may wish to just save the raw POST text and convert on-the-fly.
* # IPB does this with private messages, calendar events and announcements. In this case, you'd use the following:
* $parser->parse_html    = 0;
* $parser->parse_nl2br   = 1;
* $parser->parse_smilies = 1;
* $parser->parse_bbcode  = 1;
* $bbcode_text           = $parser->pre_db_parse( $_POST['text'] );
* $ready_to_print        = $parser->pre_display_parse( $bbcode_text );
* 
* # If you wish to convert already converted BBCode back into the raw format
* # (for use in an editing screen, for example) use this:
* $raw_post = $parser->pre_edit_parse( $parsed_text );
* 
* # Of course, if you're using the rich text editor (WYSIWYG) then you don't want to uncovert the HTML
* # otherwise the rich text editor will show unparsed BBCode tags, and not formatted HTML. In this case use this:
* $raw_post = $parser->convert_ipb_html_to_html( $parsed_text );
* </code>
*
* @package		InvisionPowerBoard
* @subpackage	BBCodeParser
* @author		Matt Mecham
* @copyright	Invision Power Services, Inc.
* @version		2.1
*/

/**
*
*/

/**
* BBCode Parsing Core Class
*
* Main object class
*
* @package		InvisionPowerBoard
* @subpackage	BBCodeParser
* @author  	 	Matt Mecham
* @version		2.1
* @since		2.1.0
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class class_bbcode_core
{
	/**
	* IPS Class Object
	*
	* @var object
	*/
	var $ipsclass;
	
	/**#@+
	* User defined setting
	* @var integer 
	*/
	var $parse_smilies    	= 0;
	var $parse_html       	= 0;
	var $parse_bbcode     	= 0;
	var $parse_custombbcode = 1;
	var $parse_wordwrap   	= 0;
	var $parse_nl2br      	= 1;
	var $strip_quotes     	= 0;
	var $allow_unicode    	= 1;
	var $bypass_badwords  	= 0;
	var $load_custom_tags 	= 0;
	var $max_embed_quotes 	= 15;
	var $parsing_signature  = 0;
	
	// Do not change this unless you have a VERY good reason...
	var $strip_hex_entity 	= 1;
	/**#@-*/
	
	/**#@+
	* Internally defined setting
	* @var integer 
	*/
	var $image_count      = 0;
	var $emoticon_count   = 0;
	var $quote_open       = 0;
	var $quote_closed     = 0;
	var $quote_error      = 0;
	/**#@-*/
	
	/**#@+
	* Internally defined setting
	* @var string 
	*/
	var $error            = "";
	var $emoticons        = "";
	var $badwords         = "";
	var $in_sig           = "";
	
	/**#@-*/
	
	/**#@+
	* Internally defined array
	* @var string 
	*/
	var $quote_html     = array();
	var $rev_font_sizes = array();
	var $font_sizes     = array( 1 => '8',
								 2 => '10',
								 3 => '12',
								 4 => '14',
								 5 => '18',
								 6 => '24',
								 7 => '36' );
	/**#@-*/
	
	/*-------------------------------------------------------------------------*/
	// Recursively parse a simple tag
	/*-------------------------------------------------------------------------*/
	
	/**
	* Recursively parse a simple tag
	*
	* @param	string	Tag name (ie "b", "i", "s")
	* @param	string	Convert tag (ie "b", "i", "strike" )
	* @param	int		To BBcode
	* @param	string	HTML to search in
	* @return	string	Parsed HTML;
	*/
	function parse_simple_tag_recursively( $tag_name, $convert_name, $bbcode, $text )
	{
		//----------------------------------------
		// INIT
		//----------------------------------------
		
		$_open    = ( $bbcode ) ? '[' : '<';
		$_close   = ( $bbcode ) ? ']' : '>';
		
		$_s_open  = ( $bbcode ) ? '<' : '[';
		$_s_close = ( $bbcode ) ? '>' : ']';
		
		$total_length = strlen( $text );
		$_text        = $text;
		$statement    = "";
	
		# Tag specifics
		$tag_open        = $_open . $tag_name . $_close;
		$found_tag_open  = 0;
		$tag_close       = $_open . "/" . $tag_name . $_close;
		$found_tag_close = 0;
		
		//----------------------------------------
		// Keep the server busy for a while
		//----------------------------------------
		
		while ( 1 == 1 )
		{
			//-----------------------------------------
			// Update template length
			//-----------------------------------------
			
			$_beginning_of_code = 0;
			$_l_text            = strtolower( $_text );
			
			//----------------------------------------
			// Look for opening [TAG].
			//----------------------------------------
			
			$found_tag_open = strpos( $_l_text, $tag_open, $found_tag_close );
			
			//----------------------------------------
			// No logic found? 
			//----------------------------------------
			
			if ( $found_tag_open === FALSE )
			{
				break;
			}
			
			//----------------------------------------
			// End [/TAG] statement?
			//----------------------------------------
			
			$found_tag_close = strpos( $_l_text, $tag_close, $found_tag_open );
			
			//----------------------------------------
			// No end statement found
			//----------------------------------------
			
			if ( $found_tag_close === FALSE )
			{ 
				return $_text;
			}
			
			$_beginning_of_code = $found_tag_open + strlen( $tag_open );
			
			//----------------------------------------
			// Check recurse
			//----------------------------------------
			
			$tag_found_recurse = $_beginning_of_code;
			
			while ( 1 == 1 )
			{
				//----------------------------------------
				// Got an IF?
				//----------------------------------------
				
				$tag_found_recurse = strpos( $_l_text, $tag_open, $tag_found_recurse );
				
				//----------------------------------------
				// None found...
				//----------------------------------------
				
				if ( $tag_found_recurse === FALSE OR $tag_found_recurse >= $found_tag_close )
				{
					break;
				}
				
				$tag_end_recurse = $found_tag_close + strlen( $tag_close );
				
				# Start at tag_found_recurse...
				$found_tag_close = strpos( $_l_text, $tag_close, $tag_found_recurse );
				
				//----------------------------------------
				// None found...
				//----------------------------------------
				
				if ( $found_tag_close === FALSE )
				{
					return $_text;
				}
				
				$tag_found_recurse += strlen( $tag_open );
			}
	
			//----------------------------------------
			// Continue
			//----------------------------------------
			
			$_code  = substr( $_text, $_beginning_of_code, $found_tag_close - $_beginning_of_code );
			
			//----------------------------------------
			// Recurse
			//----------------------------------------
			
			if ( strpos( strtolower( $_code ), $tag_open ) !== FALSE )
			{
				$_code = $this->parse_simple_tag_recursively( $tag_name, $convert_name, $bbcode, $_code );
			}
			
			//----------------------------------------
			// Swap old text for new...
			//----------------------------------------
			
			$_new_code = $_s_open . $convert_name . $_s_close . $_code . $_s_open . '/' . $convert_name . $_s_close;
			
			$_text = substr_replace( $_text, $_new_code, $found_tag_open, ( $found_tag_close - $found_tag_open ) + strlen( $tag_close )  );
			
			$found_tag_close = $found_tag_open + strlen($_new_code);
		}
	
		return $_text;
	}
	
	/*-------------------------------------------------------------------------*/
	// Global init
	/*-------------------------------------------------------------------------*/
	
	/**
	* Builds up font arrays
	*
	* @return void;
	*/
	function global_init()
	{
		//-------------------------------
		// Remap font sizes
		//-------------------------------
		
		foreach( $this->font_sizes as $bbcode => $real )
		{
			$this->rev_font_sizes[ $bbcode ] = $real;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Get real font size
	/*-------------------------------------------------------------------------*/
	
	/**
	* Convert pt size to BBCode size
	*
	* @param	integer	Real size
	* @return   integer BBCode size
	*/
	function convert_realsize_to_bbsize( $real )
	{
		$real = intval( $real );
		
		if ( $this->rev_font_sizes[ $real ] )
		{
			return $this->rev_font_sizes[ $real ];
		}
		else
		{
			return 3;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Get BBcode font size
	/*-------------------------------------------------------------------------*/
	
	/**
	* Convert BBCode size to px size
	*
	* @param	integer BBCode size
	* @return	integer Real size
	*/
	function convert_bbsize_to_realsize( $bb )
	{
		$bb = intval( $bb );
		
		if ( $this->font_sizes[ $bb ] )
		{
			return $this->font_sizes[ $bb ];
		}
		else
		{
			return 12;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Clean up IPB html
	/*-------------------------------------------------------------------------*/
	
	/**
	* Convert special IPB HTML to normal HTML
	*
	* @param	string	Raw text
	* @return	string	Converted text
	*/
	function clean_ipb_html( $t="" )
	{
		$t = $this->post_db_unparse_bbcode( $t );
		
		//-----------------------------------------
		// left, right, center
		//-----------------------------------------
		
		$t = preg_replace( "#\[(left|right|center)\](.+?)\[/\\1\]#is"  , "<div align=\"\\1\">\\2</div>", $t );
		
		//-----------------------------------------
		// Indent => Block quote
		//-----------------------------------------
		
		while( preg_match( "#\[indent\](.+?)\[/indent\]#is" , $t ) )
		{
			$t = preg_replace( "#\[indent\](.+?)\[/indent\]#is"  , "<blockquote>\\1</blockquote>", $t );
		}
		
		//-----------------------------------------
		// Quotes
		//-----------------------------------------
		
		$t = preg_replace( "#<!--QuoteBegin-->(.+?)<!--QuoteEBegin-->#"                        , '[quote]'         , $t );
		$t = preg_replace( "#<!--QuoteBegin-{1,2}([^>]+?)\+([^>]+?)-->(.+?)<!--QuoteEBegin-->#", "[quote=\\1,\\2]" , $t );
		$t = preg_replace( "#<!--QuoteBegin-{1,2}([^>]+?)\+-->(.+?)<!--QuoteEBegin-->#"        , "[quote=\\1]"     , $t );
		$t = preg_replace( "#<!--QuoteEnd-->(.+?)<!--QuoteEEnd-->#"                            , '[/quote]'        , $t );
		
		//-----------------------------------------
		// New quote
		//-----------------------------------------
	
		$t = preg_replace_callback( "#<!--quoteo([^>]+?)?-->(.+?)<!--quotec-->#si", array( &$this, '_parse_new_quote'), $t );
			
		//-----------------------------------------
		// SQL
		//-----------------------------------------
		
		$t = preg_replace_callback( "#<!--sql-->(.+?)<!--sql1-->(.+?)<!--sql2-->(.+?)<!--sql3-->#is", array( &$this, 'unconvert_sql'), $t );
		
		//-----------------------------------------
		// HTML
		//-----------------------------------------
		
		$t = preg_replace_callback( "#<!--html-->(.+?)<!--html1-->(.+?)<!--html2-->(.+?)<!--html3-->#is", array( &$this, 'unconvert_htm'), $t );
		
		//-----------------------------------------
		// CODE
		//-----------------------------------------
		
		$t = preg_replace( "#<!--c1-->(.+?)<!--ec1-->#", '[code]' , $t );
		$t = preg_replace( "#<!--c2-->(.+?)<!--ec2-->#", '[/code]', $t );
			
		//-----------------------------------------
		// Remove all comments
		//-----------------------------------------
		
		# Leave this to the editor to show?
		# If we strip comments here, the <!--size(..)--> tags won't be converted back
		//$t = preg_replace( "#\<\!\-\-(.+?)\-\-\>#is", "", $t );
		
		$t = str_replace( '&#39;'  , "'", $t );
		$t = str_replace( '&#33;'  , "!", $t );
		$t = str_replace( '&#039;' , "'", $t );
		$t = str_replace( '&apos;' , "'", $t );
		
		//-----------------------------------------
		// Clean up nbsp
		//-----------------------------------------
		
		//$t = str_replace( '&nbsp;&nbsp;&nbsp;&nbsp;', "\t", $t );
		//$t = str_replace( '&nbsp;&nbsp;'            , "  ", $t );
		
		//-----------------------------------------
		// Remove snap back macro
		//-----------------------------------------
		
		$t = preg_replace("#<a href=['\"]index.php?act=findpost&(amp;)?pid=.+?['\"]><\{.+?\}></a>#", "", $t );
		
		//-----------------------------------------
		// Remove all macros
		//-----------------------------------------
		
		$t = preg_replace( "#<\{.+?\}>#", "", $t );
		
		//-----------------------------------------
		// Opening style attributes
		//-----------------------------------------
		
		$t = preg_replace( "#<!--sizeo:(.+?)-->(.+?)<!--/sizeo-->#"               , '<font size="\\1">'       , $t );
		$t = preg_replace( "#<!--coloro:(.+?)-->(.+?)<!--/coloro-->#"             , '<font color="\\1">'      , $t );
		$t = preg_replace( "#<!--fonto:(.+?)-->(.+?)<!--/fonto-->#"               , '<font face="\\1">'       , $t );
		$t = preg_replace( "#<!--backgroundo:(.+?)-->(.+?)<!--/backgroundo-->#"   , '<font background="\\1">' , $t );
		
		//-----------------------------------------
		// Closing style attributes
		//-----------------------------------------
		
		$t = preg_replace( "#<!--sizec-->(.+?)<!--/sizec-->#"            , "</font>" , $t );
		$t = preg_replace( "#<!--colorc-->(.+?)<!--/colorc-->#"          , "</font>" , $t );
		$t = preg_replace( "#<!--fontc-->(.+?)<!--/fontc-->#"            , "</font>" , $t );
		$t = preg_replace( "#<!--backgroundc-->(.+?)<!--/backgroundc-->#", "</font>" , $t );
		
		//-----------------------------------------
		// Clean up code tags
		//-----------------------------------------
		
		//$t = preg_replace_callback( "#\[code\](.+?)\[/code\]#is"      , array( $this, 'clean_ipb_html_code_tag' ), $t );
		
		return $t;
	}
	
	/*-------------------------------------------------------------------------*/
	// Clean up code tags for RTE body
	/*-------------------------------------------------------------------------*/
	
	/**
	* Remove quote tags
	*
	* @param	array	Array of matches
	* @return	string	Converted text
	*/
	function clean_ipb_html_code_tag( $matches )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$txt = $matches[1];
		
		//-----------------------------------------
		// Fix...
		//-----------------------------------------
		
		$txt = str_replace( "<"             , "&lt;"  , $txt );
		$txt = str_replace( ">"             , "&gt;"  , $txt );
		$txt = str_replace( "&#60;"         , "&lt;"  , $txt );
		$txt = str_replace( "&#62;"         , "&gt;"  , $txt );
		$txt = str_replace( "&lt;br&gt;"    , "<br>"  , $txt );
		$txt = str_replace( "&lt;br /&gt;"  , "<br>"  , $txt );
		
		return '[code]'.$txt.'[/code]';
	}
	
	/*-------------------------------------------------------------------------*/
	// Strip quote tags
	/*-------------------------------------------------------------------------*/
	
	/**
	* Remove quote tags
	*
	* @param	string	Raw text
	* @return	string	Converted text
	*/
	function strip_quote_tags( $txt="" )
	{
		return preg_replace( "#\[QUOTE(=.+?,.+?)?\].+?\[/QUOTE\]#ism", "", $txt );
	}
	
	/*-------------------------------------------------------------------------*/
	// strip all tags
	/*-------------------------------------------------------------------------*/
	
	/**
	* Remove ALL tags
	*
	* @param	string	Raw text
	* @return	string	Converted text
	*/
	function strip_all_tags( $txt="", $pre_edit_parse=1 )
	{
		if( $pre_edit_parse == 1 )
		{
			$txt = $this->pre_edit_parse( $txt );
		}

		$txt = $this->strip_quote_tags( $txt );

		$txt = preg_replace( "#\[(.+?)\](.+?)\[/\\1\]#is", "\\2", $txt );
		
		$txt = preg_replace( "#\[attach.+?\]#is"       , ""   , $txt );

		return $txt;
	}
	
	/*-------------------------------------------------------------------------*/
	// strip all tags to formatted HTML
	/*-------------------------------------------------------------------------*/
	
	/**
	* Remove all tags, but format neatly
	*
	* @param	string	Raw text
	* @return	string	Converted text
	* @deprecated 2.1.0
	*/
	function strip_all_tags_to_formatted( $txt="" )
	{
		//$txt = $this->strip_quote_tags( $this->unconvert( $txt ) );
		//$txt = preg_replace( "#\[CODE\](.+?)\[/CODE\]#is", "<pre>\\1</pre>", $txt );
		//$txt = preg_replace( "#\[LIST\](.+?)\[/LIST\]#eis", "'<ul>' .str_replace( '[*]', '<li>', nl2br('\\1') ).'</ul>';", $txt );
		//$txt = preg_replace( "#\[LIST=.+?\](.+?)\[/LIST\]#eis", "'<ul>' .str_replace( '[*]', '<li>', nl2br('\\1') ).'</ul>';", $txt );
		//$txt = preg_replace( "#\[.+?\](.+?)\[/.+?\]#is", "\\1", $txt );
		
		return $txt;
	}
	
	/*-------------------------------------------------------------------------*/
	// PARSE POLL TAGS
	// Converts certain code tags for polling
	/*-------------------------------------------------------------------------*/
	
	/**
	* Parse poll tags
	*
	* @param	string	Raw text
	* @return	string	Converted text
	*/
	function parse_poll_tags($txt)
	{
		$txt = preg_replace( "#\[img\](.+?)\[/img\]#ie" , "\$this->regex_check_image('\\1')", $txt );
		$txt = preg_replace( "#\[url\](\S+?)\[/url\]#ie"                                       , "\$this->regex_build_url(array('html' => '\\1', 'show' => '\\1'))", $txt );
		$txt = preg_replace( "#\[url\s*=\s*\&quot\;\s*(\S+?)\s*\&quot\;\s*\](.*?)\[\/url\]#ie" , "\$this->regex_build_url(array('html' => '\\1', 'show' => '\\2'))", $txt );
		$txt = preg_replace( "#\[url\s*=\s*(\S+?)\s*\](.*?)\[\/url\]#ie"                       , "\$this->regex_build_url(array('html' => '\\1', 'show' => '\\2'))", $txt );
	
		return $txt;
	}
	
	/*-------------------------------------------------------------------------*/
	// My strip-tags. Converts HTML entities back before strippin' em
	/*-------------------------------------------------------------------------*/
	
	/**
	* Convert HTML entities before stripping them
	*
	* @param	string	Raw text
	* @return	string	Converted text
	*/
	function my_strip_tags($t="")
	{
		$t = str_replace( '&gt;', '>', $t );
		$t = str_replace( '&lt;', '<', $t );
		
		$t = strip_tags($t);
		
		//-----------------------------------------
		// Make sure nothing naughty is left...
		//-----------------------------------------
		
		$t = str_replace( '<', '&lt;', $t );
		$t = str_replace( '>', '&gt;', $t );
		
		return $t;
	}
	
	/*-------------------------------------------------------------------------*/
	// Checks opening and closing bbtags - never pre-parsed
	/*-------------------------------------------------------------------------*/
	
	/**
	* Check for custom BBcode tags
	*
	* @param	string	Raw text
	* @return	string	Converted text
	*/
	function bbcode_check($t="")
	{
		if( ! $this->parse_custombbcode )
        {
            return $t;
        }

		$count = array();
		
		if ( is_array( $this->ipsclass->cache['bbcode'] ) and count( $this->ipsclass->cache['bbcode'] ) )
		{
			foreach( $this->ipsclass->cache['bbcode'] as $r )
			{
				if ( $r['bbcode_useoption'] )
				{
					$count[ $r['bbcode_id'] ]['open']      = substr_count( strtolower($t), '['.strtolower($r['bbcode_tag']).'=' );
					$count[ $r['bbcode_id'] ]['wrongopen'] = substr_count( strtolower($t), '['.strtolower($r['bbcode_tag']).']' );
				}
				else
				{
					$count[ $r['bbcode_id'] ]['open']      = substr_count( strtolower($t), '['.strtolower($r['bbcode_tag']).']' );
					$count[ $r['bbcode_id'] ]['wrongopen'] = substr_count( strtolower($t), '['.strtolower($r['bbcode_tag']).'=' );
				}
			
				$count[ $r['bbcode_id'] ]['closed'] = substr_count( strtolower($t), '[/'.strtolower($r['bbcode_tag']).']' );
			
				//-----------------------------------------
				// check...
				//-----------------------------------------
			
				if ( $count[ $r['bbcode_id'] ]['open'] != $count[ $r['bbcode_id'] ]['closed'] )
				{
					if ( $count[ $r['bbcode_id'] ]['wrongopen'] == $count[ $r['bbcode_id'] ]['closed'] )
					{
						$this->error = 'custom_tags_incorrect2';
					}
					else
					{
						$this->error = 'custom_tags_incorrect';
					}
				}
			}
		}
		
		return $t;
	}
	
	/*-------------------------------------------------------------------------*/
	// Post DB parse BBCode
	/*-------------------------------------------------------------------------*/
	
	/**
	* Pre-display parse custom BBCode
	*
	* @param	string	Raw text
	* @return	string	Converted text
	*/
	function post_db_parse_bbcode($t="")
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$snapback = 0;
		
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( is_array( $this->ipsclass->cache['bbcode'] ) and count( $this->ipsclass->cache['bbcode'] ) )
		{
			foreach( $this->ipsclass->cache['bbcode'] as $row )
			{
				if ( strtolower($row['bbcode_tag']) == 'snapback' )
				{
					$snapback = 1;
				}
				
				$preg_tag = preg_quote($row['bbcode_tag'], '#' );
				
				//-----------------------------------------
				// Slightly slower
				//-----------------------------------------
				
				if ( $row['bbcode_useoption'] )
				{
					while (preg_match_all( "#(\[".$preg_tag."=(?:&quot;|&\#39;|\"|\')?(.+?)(?:&quot;|&\#39;|\"|\')?\])((?R)|.*?)(\[/".$preg_tag."\])#si", $t, $match ))
					{
						for ( $i = 0; $i < count($match[0]); $i++)
						{
							//-----------------------------------------
							// Does the option tag come first?
							//-----------------------------------------
							
							$_option  = 2;
							$_content = 3;
							
							if ( $row['bbcode_switch_option'] )
							{
								$_option  = 3;
								$_content = 2;
							}
							
							# XSS Check: Bug ID: 980
							if ( $row['bbcode_tag'] == 'post' OR $row['bbcode_tag'] == 'topic' OR $row['bbcode_tag'] == 'snapback' )
							{
								$match[ $_option ][$i] = intval( $match[ $_option ][$i] );
							}
							
							# Recurse?
							if ( preg_match( "#\[.+?\]#s", $match[ $_content ][$i] ) )
							{
								$match[ $_content ][$i] = $this->post_db_parse_bbcode( $match[ $_content ][$i] );
							}
								
							$tmp = $row['bbcode_replace'];
							$tmp = str_replace( '{option}' , $match[ $_option  ][$i], $tmp );
							$tmp = str_replace( '{content}', $match[ $_content ][$i], $tmp );
							$t   = str_replace( $match[0][$i], $tmp, $t );
						}
					}
				}
				else
				{
					# Tricky.. match anything that's not a closing tag, or nothing

					while (preg_match_all( "#(\[$preg_tag\])((?R)|.*?)(\[/$preg_tag\])#si", $t, $match ))
					{
						for ( $i = 0; $i < count($match[0]); $i++)
						{
							# Recurse?
							if ( preg_match( "#\[.+?\]#s", $match[2][$i] ) )
							{
								$match[2][$i] = $this->post_db_parse_bbcode( $match[2][$i] );
							}
							
							# XSS Check: Bug ID: 980
							if ( $row['bbcode_tag'] == 'snapback' )
							{
								$match[2][$i] = intval( $match[2][$i] );
							}
							
							$tmp = $row['bbcode_replace'];
							$tmp = str_replace( '{content}', $match[2][$i], $tmp );
							$t   = str_replace( $match[0][$i], $tmp, $t );
						}
					}
				}
			}
		}
		
		//-----------------------------------------
		// Snapback used?
		//-----------------------------------------
		
		if ( ! $snapback )
		{
			$t = preg_replace( "#\[snapback\]([0-9]+?)\[/snapback\]#is", "<a href='index.php?act=findpost&amp;pid=\\1'><{POST_SNAPBACK}></a>", $t );
		}
		
		return $t;
	}
	

	/*-------------------------------------------------------------------------*/
	// Post DB UNparse BBCode
	/*-------------------------------------------------------------------------*/
	
	/**
	* Pre-edit unparse custom BBCode
	*
	* @param	string	Converted text
	* @return	string	Raw text
	*/
	function post_db_unparse_bbcode($t="")
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$snapback = 0;

		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( is_array( $this->ipsclass->cache['bbcode'] ) and count( $this->ipsclass->cache['bbcode'] ) )
		{
			foreach( $this->ipsclass->cache['bbcode'] as $row )
			{
				if ( strtolower($row['bbcode_tag']) == 'snapback' )
				{
					$snapback = 1;
				}
				
				$preg_tag = preg_quote( $row['bbcode_replace'], '#' );

				//NK: only return the first match
				$preg_tag = preg_replace( '/\\\{option\\\}/', '(.*?)', $preg_tag, 1 );
				$preg_tag = preg_replace( '/\\\{content\\\}/', '(.*?)', $preg_tag, 1 );
				$preg_tag = str_replace( '\{option\}', '.*?', $preg_tag );
				$preg_tag = str_replace( '\{content\}', '.*?', $preg_tag );
				/*$preg_tag = str_replace( '\{option\}', '.(*?)', $preg_tag );
				$preg_tag = str_replace( '\{content\}', '(.*?)', $preg_tag );*/
				
				// Bug 5658 - </span> tags in custom bbcode don't play nice with inbuilt bbcode
				$preg_tag = str_replace( "\</span\>", "\</span\>(?!\<\!--/sizec|\<\!--/colorc|\<\!--/fontc|\<\!--/backgroundc)", $preg_tag );

				//-----------------------------------------
				// Slightly slower
				//-----------------------------------------
				
				while ( preg_match_all( "#".$preg_tag."#si", $t, $match ) )
				{
					for ( $i = 0; $i < count($match[0]); $i++)
					{
						//-----------------------------------------
						// Does the option tag come first?
						//-----------------------------------------
						
						$_option  = 1;
						$_content = 2;
						
						if ( $row['bbcode_switch_option'] )
						{
							$_option  = 2;
							$_content = 1;
						}
						else if( count( $match ) == 2 )
						{
							$_content = 1;
						}
						
						# XSS Check: Bug ID: 980
						if ( $row['bbcode_tag'] == 'post' OR $row['bbcode_tag'] == 'topic' OR $row['bbcode_tag'] == 'snapback' )
						{
							$match[ $_option ][$i] = intval( $match[ $_option ][$i] );
						}
						
						# Recurse?
						if ( preg_match( "#".$preg_tag."#si", $match[ $_content ][$i] ) )
						{
							$match[ $_content ][$i] = $this->post_db_parse_bbcode( $match[ $_content ][$i] );
						}
							
						$tmp = '[' . $row['bbcode_tag'];
						
						if( $row['bbcode_useoption'] )
						{
							if( $row['bbcode_switch_option'] )
							{
								$tmp .= '={content}]{option}[/' . $row['bbcode_tag'] . ']';
							}
							else
							{
								$tmp .= '={option}]{content}[/' . $row['bbcode_tag'] . ']';
							}
						}
						else
						{
							$tmp .= ']{content}[/' . $row['bbcode_tag'] . ']';
						}
						
						$tmp = str_replace( '{option}' , $match[ $_option  ][$i], $tmp );
						$tmp = str_replace( '{content}', $match[ $_content ][$i], $tmp );
						$t   = str_replace( $match[0][$i], $tmp, $t );
					}
				}
			}
		}
		
		//-----------------------------------------
		// Snapback used?
		//-----------------------------------------
		
		if ( ! $snapback )
		{
			$t = preg_replace( "#\<a href=\'index\.php\?act=findpost&amp;pid=([0-9]+?)\'\>\<\{POST_SNAPBACK\}\>\<\/a\>#is", "[snapback]\\1[/snapback]", $t );
		}
	
		return $t;
	}
	
	//-----------------------------------------
	// Word wrap, wraps 'da word innit
	//-----------------------------------------
	
	/**
	* Custom word wrap
	*
	* @param	string	Raw text
	* @return	string	Converted text
	*/
	function my_wordwrap($t="", $chrs=0, $replace="<br />")
	{
		if ( $t == "" )
		{
			return $t;
		}
		
		if ( $chrs < 1 )
		{
			return $t;
		}
#var_dump( $t );
		$t = preg_replace("#([^\s<>'\"/\\-\?&\n\r\%]{".$chrs."})([^\s<>'\"/\\-\?&\n\r\%]{1})#i", "\\1".$replace."\\2" ,$t);

#print $chrs.' '.var_dump($replace).var_dump( $t );exit;
		return $t;
	}
	
	//-----------------------------------------
	// parse_html
	// Converts the doHTML tag
	//-----------------------------------------
	
	/**
	* Pre-display convert HTML entities for use
	* when HTML is enabled
	*
	* @param	string	Raw text
	* @return	string	Converted text
	*/
	function post_db_parse_html($t="")
	{
		if ( $t == "" )
		{
			return $t;
		}
		
		//-----------------------------------------
		// Remove <br>s 'cos we know they can't
		// be user inputted, 'cos they are still
		// &lt;br&gt; at this point :)
		//-----------------------------------------
		
		if ( $this->parse_nl2br != 1 )
		{
			$t = str_replace( "<br>"    , "\n" , $t );
			$t = str_replace( "<br />"  , "\n" , $t );
		}
		
		$t = str_replace( "&#39;"   , "'", $t );
		$t = str_replace( "&#33;"   , "!", $t );
		$t = str_replace( "&#036;"   , "$", $t );
		$t = str_replace( "&#124;"  , "|", $t );
		$t = str_replace( "&amp;"   , "&", $t );
		$t = str_replace( "&gt;"    , ">", $t );
		$t = str_replace( "&lt;"    , "<", $t );
		$t = str_replace( "&quot;"  , '"', $t );
		
		//-----------------------------------------
		// Take a crack at parsing some of the nasties
		// NOTE: THIS IS NOT DESIGNED AS A FOOLPROOF METHOD
		// AND SHOULD NOT BE RELIED UPON!
		//-----------------------------------------
		
		$t = preg_replace( "/javascript/i" , "j&#097;v&#097;script"	, $t );
		$t = preg_replace( "/alert/i"      , "&#097;lert"         	, $t );
		$t = preg_replace( "/about:/i"     , "&#097;bout:"         	, $t );
		$t = preg_replace( "/onmouseover/i", "&#111;nmouseover"    	, $t );
		$t = preg_replace( "/onclick/i"    , "&#111;nclick"        	, $t );
		$t = preg_replace( "/onload/i"     , "&#111;nload"         	, $t );
		$t = preg_replace( "/onsubmit/i"   , "&#111;nsubmit"       	, $t );
        $t = preg_replace( "/onmouseout/i" , "&#111;nmouseout"		, $t );
        $t = preg_replace( "/onunload/i"   , "&#111;nunload"   		, $t );
        $t = preg_replace( "/onabort/i"    , "&#111;nabort"    		, $t );
        $t = preg_replace( "/onerror/i"    , "&#111;nerror"    		, $t );
        $t = preg_replace( "/onblur/i"     , "&#111;nblur"     		, $t );
        $t = preg_replace( "/onchange/i"   , "&#111;nchange"   		, $t );
        $t = preg_replace( "/onfocus/i"    , "&#111;nfocus"    		, $t );
        $t = preg_replace( "/onreset/i"    , "&#111;nreset"    		, $t );
        $t = preg_replace( "/ondblclick/i" , "&#111;ndblclick" 		, $t );
        $t = preg_replace( "/onkeydown/i"  , "&#111;nkeydown"  		, $t );
        $t = preg_replace( "/onkeypress/i" , "&#111;nkeypress" 		, $t );
        $t = preg_replace( "/onkeyup/i"    , "&#111;nkeyup"    		, $t );
        $t = preg_replace( "/onmousedown/i", "&#111;nmousedown"		, $t );
        $t = preg_replace( "/onmouseup/i"  , "&#111;nmouseup"  		, $t );
        $t = preg_replace( "/onselect/i"   , "&#111;nselect"   		, $t );		
		
		return $t;
	}
	
	//-----------------------------------------
	// Badwords:
	// Swops naughty, naugty words and stuff
	//-----------------------------------------
	
	/**
	* Replace bad words
	*
	* @param	string	Raw text
	* @return	string	Converted text
	*/
	function bad_words($text = "")
	{
		if ($text == "")
		{
			return "";
		}
		
		if ( $this->bypass_badwords == 1 )
		{
			return $text;
		}
		
		$temp_text = $text;
		
		for( $i = 65; $i <= 90; $i++ )
		{
			$text = str_replace( "&#".$i.";", chr($i), $text );
		}
		
		for( $i = 97; $i <= 122; $i++ )
		{
			$text = str_replace( "&#".$i.";", chr($i), $text );
		}		
		
		//-----------------------------------------
		// Go all loopy
		//-----------------------------------------
		
		if ( is_array( $this->ipsclass->cache['badwords'] ) )
		{
			if ( count($this->ipsclass->cache['badwords']) > 0 )
			{
				foreach($this->ipsclass->cache['badwords'] as $r)
				{
					if ($r['swop'] == "")
					{
						$replace = '######';
					}
					else
					{
						$replace = $r['swop'];
					}
					
					$r['type'] = preg_quote($r['type'], "/");
					
					if ($r['m_exact'] == 1)
					{
						$text = preg_replace( "/(^|\b|\s|\<br \/\>)".$r['type']."(\b|!|\?|\.|,|$)/i", "\\1{$replace}", $text );
					}
					else
					{
						//----------------------------
						// 'ass' in 'class' kills css
						//----------------------------
						
						if( $r['type'] == 'ass' && !$r['m_exact'] )
						{
							$text = preg_replace( "/(?<!cl)".$r['type']."/i", "$replace", $text );
						}
						else
						{
							$text = preg_replace( "/".$r['type']."/i", "$replace", $text );
						}
					}
				}
			}
		}
		
		return $text ? $text : $temp_text;
	}
	
	/*-------------------------------------------------------------------------*/
	// wrap style: code and quote table HTML generator
	/*-------------------------------------------------------------------------*/
	
	/**
	* Wrap quote / code / html / sql divs
	*
	* @param	string	Type
	* @param	string	Extra vars
	* @return	array	Converted text
	*/
	function wrap_style( $type='quote', $extra="" )
	{
		$used = array(
					   'quote' => array( 'title' => "{$this->ipsclass->lang['bbcode_wrap_quote']}", 'css_top' => 'quotetop' , 'css_main' => 'quotemain' ),
					   'code'  => array( 'title' => "{$this->ipsclass->lang['bbcode_wrap_code']}" , 'css_top' => 'codetop'  , 'css_main' => 'codemain'  ),
					   'sql'   => array( 'title' => "{$this->ipsclass->lang['bbcode_wrap_sql']}"  , 'css_top' => 'sqltop'   , 'css_main' => 'sqlmain'   ),
					   'html'  => array( 'title' => "{$this->ipsclass->lang['bbcode_wrap_html']}" , 'css_top' => 'htmltop'  , 'css_main' => 'htmlmain'  )
					 );
		
		$this->wrap_top    = "<div class='{$used[ $type ]['css_top']}'>{$used[ $type ]['title']}{$extra}</div><div class='{$used[ $type ]['css_main']}'>";
	    $this->wrap_bottom = "</div>";
			
		/*if ( ! $this->wrap_top )
		{
			
		
			if ( $this->ipsclass->compiled_templates['skin_global'] )
			{
				$this->wrap_top    = $this->ipsclass->compiled_templates['skin_global']->bbcode_wrap_start();
				$this->wrap_bottom = $this->ipsclass->compiled_templates['skin_global']->bbcode_wrap_end();
				
				$this->wrap_top = str_replace( '<!--css.top-->' , "{$used[ $type ]['css_top']}" , $this->wrap_top );
				$this->wrap_top = str_replace( '<!--css.main-->', "{$used[ $type ]['css_main']}", $this->wrap_top );
				$this->wrap_top = str_replace( '<!--title-->'   , "{$used[ $type ]['title']}"   , $this->wrap_top );
				$this->wrap_top = str_replace( '<!--extra-->'   , $extra                        , $this->wrap_top );
			}
		}*/
		
		return array( 'START' => $this->wrap_top, 'END' => $this->wrap_bottom );
	}

	/*-------------------------------------------------------------------------*/
	// regex_html_tag: HTML syntax highlighting
	/*-------------------------------------------------------------------------*/
	
	/**
	* Custom HTML syntax highlighting
	*
	* @param	string	Raw text
	* @return	string	Converted text
	*/
	function regex_html_tag( $matches=array() )
	{
		$html = trim($matches[1]);
		
		if ($html == "")
		{
			return;
		}
		
		//-----------------------------------------
		// Take a stab at removing most of the common
		// smilie characters.
		//-----------------------------------------
		
		$html = str_replace( ":"     , "&#58;", $html );
		$html = str_replace( "["     , "&#91;", $html );
		$html = str_replace( "]"     , "&#93;", $html );
		$html = str_replace( ")"     , "&#41;", $html );
		$html = str_replace( "("     , "&#40;", $html );
		$html = str_replace( "{"	 , "&#123;", $html );
		$html = str_replace( "}"	 , "&#125;", $html );
		$html = str_replace( "$"	 , "&#36;", $html );
		
		$html = preg_replace( "/^<br>/"  , "", $html );
		$html = preg_replace( "#^<br />#", "", $html );
		$html = preg_replace( "/^\s+/"   , "", $html );

		$html = preg_replace( "#&lt;([^&<>]+)&gt;#"                            , "&lt;<span style='color:blue'>\\1</span>&gt;"        , $html );   //Matches <tag>
		$html = preg_replace( "#&\#60;([a-zA-Z0-9]+)([&gt;|\s])#"              , "&#60;<span style='color:blue'>\\1</span>\\2"        , $html );   //Matches <script[ |>]
		$html = preg_replace( "#&lt;([^&<>]+)\s#"                              , "&lt;<span style='color:blue'>\\1</span> "          , $html );   //Matches <tag
		$html = preg_replace( "#&lt;/([^&]+)&gt;#"                             , "&lt;/<span style='color:blue'>\\1</span>&gt;"       , $html );   //Matches </tag>
		$html = preg_replace( "!=(&quot;|&#39;)(.+?)?(&quot;|&#39;)(\s|&gt;)!" , "=\\1<span style='color:orange'>\\2</span>\\3\\4"    , $html );   //Matches ='this'
		$html = preg_replace( "!&#60;&#33;--(.+?)--&#62;!s"                    , "&lt;&#33;<span style='color:red'>--\\1--</span>&gt;", $html );
		
		$wrap = $this->wrap_style( 'html' );
		
		return "<!--html-->{$wrap['START']}<!--html1-->$html<!--html2-->{$wrap['END']}<!--html3-->";
	}
	
	/*-------------------------------------------------------------------------*/
	// regex_sql_tag: SQL syntax highlighting
	/*-------------------------------------------------------------------------*/
	
	/**
	* Custom SQL syntax highlighting
	*
	* @param	string	Raw text
	* @return	string	Converted text
	*/
	function regex_sql_tag( $matches=array() )
	{
		$sql = trim($matches[1]);
		
		if ($sql == "")
		{
			return;
		}
		
		//-----------------------------------------	
		// Knock off any preceeding newlines (which have
		// since been converted into <br>)
		//-----------------------------------------
		
		$sql = preg_replace( "/^<br>/"  , "", $sql );
		$sql = preg_replace( "#^<br />#", "", $sql );
		$sql = preg_replace( "/^\s+/"   , "", $sql );
		
		//-----------------------------------------
		// Make certain regex work..
		//-----------------------------------------
		
		if (! preg_match( "/\s+$/" , $sql) )
		{
			$sql = $sql.' ';
		}
		
		$sql = str_replace( "$"	 , "&#36;", $sql );
		//print $sql;exit;
		$sql = preg_replace( "#(=|\+|\-|&gt;|&lt;|~|==|\!=|LIKE|NOT LIKE|REGEXP)#i"            , "<span style='color:orange'>\\1</span>", $sql );
		$sql = preg_replace( "#(MAX|AVG|SUM|COUNT|MIN)\(#i"                                    , "<span style='color:blue'>\\1</span>("    , $sql );
		$sql = preg_replace( "#(FROM|INTO)\s{1,}(\S+?)\s{1,}((\w+)\s{0,})#i"                   , "<span style='color:green'>\\1</span> <span style='color:orange'>\\2</span> <span style='color:orange'>\\3</span>", $sql );
		//$sql = preg_replace( "#,\s{0,}(\S+?)\s{1,}(\w+)\s{0,}#i"                                , ", <span style='color:orange'>\\2</span> <span style='color:orange'>\\3</span> ", $sql );
		$sql = preg_replace( "#(?<=join)\s{1,}(\S+?)\s{1,}(\w+)\s{0,}#i"                       , " <span style='color:orange'>\\1</span> <span style='color:orange'>\\2</span> ", $sql );
	    $sql = preg_replace( "!(&quot;|&#39;|&#039;)(.+?)(&quot;|&#39;|&#039;)!i"              , "<span style='color:red'>\\1\\2\\3</span>" , $sql );
	    $sql = preg_replace( "#\s{1,}(AND|OR|ON)\s{1,}#i"                                         , " <span style='color:blue'>\\1</span> "    , $sql );
	    $sql = preg_replace( "#(LEFT|JOIN|WHERE|MODIFY|CHANGE|AS|DISTINCT|IN|ASC|DESC|ORDER BY)\s{1,}#i" , "<span style='color:green'>\\1</span> "   , $sql );
	    $sql = preg_replace( "#LIMIT\s*(\d+)(?:\s*([,])\s*(\d+))*#i"                                    , "<span style='color:green'>LIMIT</span> <span style='color:orange'>\\1\\2 \\3</span>" , $sql );
	    $sql = preg_replace( "#(SELECT|INSERT|UPDATE|DELETE|ALTER TABLE|CREATE TABLE|DROP)#i"               , "<span style='color:blue;font-weight:bold'>\\1</span>" , $sql );

	    $html = $this->wrap_style( 'sql' );
	    
	    return "<!--sql-->{$html['START']}<!--sql1-->{$sql}<!--sql2-->{$html['END']}<!--sql3-->";
	}
	
	/*-------------------------------------------------------------------------*/
	// regex_code_tag: Builds this code tag HTML
	/*-------------------------------------------------------------------------*/
	
	/**
	* Build code tag, make contents safe
	*
	* @param	string	Raw text
	* @return	string	Converted text
	*/
	function regex_code_tag( $matches=array() )
	{
		//-----------------------------------------
		// We don't want to trim indentations on the first line
		//-----------------------------------------
		
		$txt = rtrim( $matches[1] );
		$txt = preg_replace( "#^(\n+)(.+?)$#s", "\\2", $txt );
		
		$default = "\[code\]$txt\[/code\]";
		
		if ( $txt == "" )
		{
			return;
		}
		
		//-----------------------------------------
		// Take a stab at removing most of the common
		// smilie characters.
		//-----------------------------------------
		
		//$txt = str_replace( "&" , "&amp;", $txt );
		$txt = str_replace( "<"         , "&#60;" , $txt );
		$txt = str_replace( ">"         , "&#62;" , $txt );
		$txt = str_replace( "&lt;"      , "&#60;" , $txt );
		$txt = str_replace( "&gt;"      , "&#62;" , $txt );
		$txt = str_replace( "&quot;"    , "&#34;" , $txt );
		$txt = str_replace( ":"         , "&#58;" , $txt );
		$txt = str_replace( "["         , "&#91;" , $txt );
		$txt = str_replace( "]"         , "&#93;" , $txt );
		$txt = str_replace( ")"         , "&#41;" , $txt );
		$txt = str_replace( "("         , "&#40;" , $txt );
		$txt = str_replace( "\r"        , "<br />", $txt );
		$txt = str_replace( "\n"        , "<br />", $txt );
		$txt = preg_replace( "#\s{1};#" , "&#59;" , $txt );
		
		//-----------------------------------------
		// Ensure that spacing is preserved
		//-----------------------------------------
		
		$txt = preg_replace( "#\t#"   , "&nbsp;&nbsp;&nbsp;&nbsp;", $txt );
		$txt = preg_replace( "#\s{2}#", "&nbsp;&nbsp;"            , $txt );

		$html = $this->wrap_style( 'code' );

		return "<!--c1-->{$html['START']}<!--ec1-->$txt<!--c2-->{$html['END']}<!--ec2-->";
	}
	
	/*-------------------------------------------------------------------------*/
	// regex_check_image: Checks, and builds the <img>
	/*-------------------------------------------------------------------------*/
	
	/**
	* Check image URL and return HTML
	*
	* @param	string	URL
	* @return	string	IMG tag HTML
	*/
	function regex_check_image( $matches=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$url = $matches[1];
		
		if ( ! $url )
		{
			return;
		}
		
		$url = trim($url);
		
		$default = "[img]".str_replace( '[', '&#091;', $url )."[/img]";
		
		$this->image_count++;
	
		//-----------------------------------------
		// Make sure we've not overriden the set image # limit
		//-----------------------------------------

		if ( $this->ipsclass->vars['max_images'] )
		{
			if ($this->image_count > $this->ipsclass->vars['max_images'])
			{
				$this->error = 'too_many_img';
				return $default;
			}
		}
		
		//-----------------------------------------
		// XSS check
		//-----------------------------------------
		
		$url = urldecode( $url );
		$url = str_replace( "document.cookie", "" , $url );
		$url = str_replace( "%20"            , " ", $url );
		
		//-----------------------------------------
		// Are they attempting to post a dynamic image, or JS?
		//-----------------------------------------
		
		if ( $this->ipsclass->vars['allow_dynamic_img'] != 1 )
		{
			if ( preg_match( "/[?&<]/", $url) )
			{
				var_dump($url);
				$this->error = 'no_dynamic';
				return $default;
			}
			
			if ( preg_match( "/javascript\:/is", preg_replace( "#/\s{1,}#s", "", $url ) ) )
			{
				$this->error = 'no_dynamic';
				return $default;
			}
		}
		
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( $this->ipsclass->xss_check_url( $url ) !== TRUE )
		{
			return '';
		}
		
		//-----------------------------------------
		// Is the img extension allowed to be posted?
		//-----------------------------------------
		
		if ( $this->ipsclass->vars['img_ext'] )
		{
			$extension = preg_replace( "#^.*\.(\w+)(\?.*$|$)#", "\\1", $url );
			
			$extension = strtolower($extension);
			
			if ( (! $extension) OR ( preg_match( "#/#", $extension ) ) )
			{
				$this->error = 'invalid_ext';
				return $default;
			}
			
			$this->ipsclass->vars['img_ext'] = strtolower($this->ipsclass->vars['img_ext']);
			
			if ( ! preg_match( "/".preg_quote($extension, '/')."(,|$)/", $this->ipsclass->vars['img_ext'] ))
			{
				$this->error = 'invalid_ext';
				return $default;
			}
		}
		
		//-----------------------------------------
		// URL filtering?
		//-----------------------------------------
		
		if ( $this->ipsclass->vars['ipb_use_url_filter'] )
		{
			$list_type = $this->ipsclass->vars['ipb_url_filter_option'] == "black" ? "blacklist" : "whitelist";
			
			if( $this->ipsclass->vars['ipb_url_'.$list_type] )
			{
				$list_values = array();
				$list_values = explode( "\n", str_replace( "\r", "", $this->ipsclass->vars['ipb_url_'.$list_type] ) );
				
				if ( count( $list_values ) )
				{
					$good_url = 0;
					
					foreach( $list_values as $my_url )
					{
						if( !trim($my_url) )
						{
							continue;
						}

						$my_url = preg_quote( $my_url, '/' );
						$my_url = str_replace( "\*", "(.*?)", $my_url );
						
						if ( $list_type == "blacklist" )
						{
							if( preg_match( '/'.$my_url.'/i', $url ) )
							{
								$this->error = 'domain_not_allowed';
							}
						}
						else
						{
							if ( preg_match( '/'.$my_url.'/i', $url ) )
							{
								$good_url = 1;
							}
						}
					}
					
					if ( ! $good_url AND $list_type == "whitelist" )
					{
						$this->error = 'domain_not_allowed';
					}					
				}
			}
		}		
		
		//-----------------------------------------
		// Is it a legitimate image?
		//-----------------------------------------
		
		/*if ( ! preg_match( "/^(http|https|ftp):\/\//i", $url ) )
		{
			$this->error = 'no_dynamic';
			return $default;
		}*/
		
		//-----------------------------------------
		// If we are still here....
		//-----------------------------------------
		
		$url = preg_replace( "#\s{1,}#", "%20", $url );
		
		//-----------------------------------------
		// Signature?
		//-----------------------------------------

		$_class = ( $this->parsing_signature ) ? 'linked-sig-image' : 'linked-image';

		// If we add an alt tag - won't be able to delete image in RTE
		return "<img src=\"$url\" border=\"0\" class=\"". $_class ."\" />";
	}
	
	/*-------------------------------------------------------------------------*/
	// _regex_font_attr_background:
	/*-------------------------------------------------------------------------*/
	
	/**
	* Helper function
	*
	* @param	array	Value or preg_replace_callback
	* @return	string	Converted text
	*/
	function _regex_font_attr_background( $matches=array() )
	{
		return $this->regex_font_attr( array( 's' => 'background',
											  '1' => $matches[1],
											  '2' => $matches[2] ) ); 
	}
	
	/*-------------------------------------------------------------------------*/
	// _regex_font_attr_font:
	/*-------------------------------------------------------------------------*/
	
	/**
	* Helper function
	*
	* @param	array	Value or preg_replace_callback
	* @return	string	Converted text
	*/
	function _regex_font_attr_font( $matches=array() )
	{
		return $this->regex_font_attr( array( 's' => 'font',
											  '1' => $matches[1],
											  '2' => $matches[2] ) ); 
	}
	
	/*-------------------------------------------------------------------------*/
	// _regex_font_attr_size:
	/*-------------------------------------------------------------------------*/
	
	/**
	* Helper function
	*
	* @param	array	Value or preg_replace_callback
	* @return	string	Converted text
	*/
	function _regex_font_attr_size( $matches=array() )
	{
		return $this->regex_font_attr( array( 's' => 'size',
											  '1' => $matches[1],
											  '2' => $matches[2] ) ); 
	}
	
	/*-------------------------------------------------------------------------*/
	// _regex_font_attr_color:
	/*-------------------------------------------------------------------------*/
	
	/**
	* Helper function
	*
	* @param	array	Value or preg_replace_callback
	* @return	string	Converted text
	*/
	function _regex_font_attr_color( $matches=array() )
	{
		return $this->regex_font_attr( array( 's' => 'col',
											  '1' => $matches[1],
											  '2' => $matches[2] ) ); 
	}
	
    /*-------------------------------------------------------------------------*/
	// regex_font_attr:
	// Returns a string for an /e regexp based on the input
	/*-------------------------------------------------------------------------*/
	
	/**
	* Convert size / color / font BBCode tags
	*
	* @param	array	Vars
	* @return	string	Converted text
	*/
	function regex_font_attr( $IN )
	{
		if ( ! is_array($IN) )
		{
			return;
		}
		
		//-----------------------------------------
		// Make safe
		//-----------------------------------------
		
		$IN['1'] = preg_replace( "/[&\(\)\.\%\[\]<>]/", "", preg_replace( "#^(.+?)(?:;|$)#", "\\1", $IN['1'] ) );
		
		//-----------------------------------------
		// Size
		//-----------------------------------------
		
		if ($IN['s'] == 'size')
		{
			$IN['1'] = intval($IN['1']) + 7;
			
			if ($IN['1'] > 30)
			{
				$IN['1'] = 30;
			}
			
			return "<span style='font-size:".$IN['1']."pt;line-height:100%'>".$IN['2']."</span>";
		}
		
		//-----------------------------------------
		// BACKGROUND
		//-----------------------------------------
		
		else if ($IN['s'] == 'background')
		{
			$IN[1] = preg_replace( "/[^\d\w\#\s]/s", "", $IN[1] );
			return "<span style='background-color:".$IN[1]."'>".$IN['2']."</span>";
		}
		
		//-----------------------------------------
		// COLOR
		//-----------------------------------------
		
		else if ($IN['s'] == 'col')
		{
			$IN[1] = preg_replace( "/[^\d\w\#\s]/s", "", $IN[1] );
			return "<span style='color:".$IN[1]."'>".$IN['2']."</span>";
		}
		
		//-----------------------------------------
		// FONT
		//-----------------------------------------
		
		else if ($IN['s'] == 'font')
		{
			$IN['1'] = preg_replace( "/[^\d\w\#\-\_\s]/s", "", $IN['1'] );
			
			if( $this->ipsclass->browser['browser'] == 'opera' )
			{
				// Needs doublequotes around font names with more than one word
				
				return "<span style='font-family:\"".$IN['1']."\"'>".$IN['2']."</span>";
			}
			else
			{
				return "<span style='font-family:".$IN['1']."'>".$IN['2']."</span>";
			}
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// regex_list: List generation
	/*-------------------------------------------------------------------------*/
	
	/**
	* Convert list BBCode
	*
	* @param	string	Raw text
	* @param	string	List type
	* @return	string	Converted text
	*/
	function regex_list( $matches=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$types = array( 'a', 'A', 'i', 'I', '1' );

		if ( in_array( $matches[2], $types ) )
		{
			$fnl	= $matches[1];
			$type	= $matches[2];
			$txt 	= $matches[3];
			$lnl	= $matches[4];
		}
		else
		{
			$fnl	= $matches[1];
			$txt	= $matches[2];
			$lnl	= $matches[3];
		}

		if ( $txt == "" )
		{
			return;
		}
		
		$txt = stripslashes( $txt );
		
		if ( $type == "" )
		{
			return $fnl . "<ul>".$this->regex_list_item($txt)."</ul>" . $lnl;
		}
		else
		{
			return $fnl . "<ol type='$type'>".$this->regex_list_item($txt)."</ol>" . $lnl;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Regex list item
	/*-------------------------------------------------------------------------*/
	
	/**
	* Convert list item
	*
	* @param	string	Raw text
	* @return	string	Converted text
	*/
	function regex_list_item($txt)
	{
		$txt = preg_replace( "#\[\*\]#", "</li><li>" , trim($txt) );
		
		$txt = preg_replace( "#^</?li>#"  , "", $txt );
		
		return str_replace( "\n</li>", "</li>", $txt."</li>" );
	}
	
			
	/*-------------------------------------------------------------------------*/
	// regex_parse_quotes: Builds this quote tag HTML
	/*-------------------------------------------------------------------------*/
	
	/**
	* Parse quotes: main
	*
	* @param	string	Raw text
	* @return	string	Converted text
	*/
	function regex_parse_quotes( $matches=array() )
	{
		$the_txt = trim( $matches[1] );
		
		$this->quote_open   = 0;
		$this->quote_closed = 0;
		$this->quote_error  = 0;
		
		if ($the_txt == "") return;
		
		$txt = $the_txt;
		
		if ( substr_count( strtolower($txt), '[quote' ) > $this->max_embed_quotes )
		{
			$this->error = 'too_many_quotes';
			
			return $txt;
		}
		
		//print $txt . "<hr>";
		
		$txt = str_replace( chr(173).']', '&#93;', $txt );
	
		// Trim quoted text
		$txt = preg_replace_callback( "#\[quote([^\]]+?)?\](.+?)\[/quote\]#si", array( &$this, 'regex_trim_quote' ), $txt );
		
		// Clean usernames with square brackets
		$txt = preg_replace_callback( "#(name=(?:&\#39;|&quot;|'|\"))(.+?)(&\#39;|&quot;|'|\")#si", array( &$this, '_make_quote_safe' ), $txt );
		
		// Clean usernames with quotes....
		$txt = preg_replace_callback( "#name=(&\#39;|&quot;|'|\")(.+?)(\\1)\s?(post|date)=#si", array( &$this, '_make_quote_name_safe' ), $txt );
		
		$this->quote_html = $this->wrap_style('quote');
		
		$txt = preg_replace_callback( "#\[/quote\]#i"                    , array( &$this, 'regex_close_quote' )     , $txt );
		$txt = preg_replace_callback( "#\[quote=([^\],]+?),([^\]]+?)\]#i", array( &$this, 'regex_quote_tag' )       , $txt );
		$txt = preg_replace_callback( "#\[quote=([^\]]+?)\]#i"           , array( &$this, 'regex_quote_tag' )       , $txt );
		$txt = preg_replace_callback( "#\[quote([^\]]+?)?\]#i"           , array( &$this, 'regex_simple_quote_tag' ), $txt );
		
		$txt = str_replace( "\n", "<br />", $txt );
		
		# Final clean...
		$txt = str_replace( "&#0039;", "&#39;", $txt );
		
		//print htmlspecialchars( $txt ); exit();
		
		if ( ($this->quote_open == $this->quote_closed) and ($this->quote_error == 0) )
		{
			return $txt;
		}
		else
		{
			$this->error = 'quote_mismatch';

			return $the_txt;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// regex_trim_quote: Trims quoted text
	/*-------------------------------------------------------------------------*/
			
	function regex_trim_quote( $matches=array() )
	{
		$txt   = $matches[2];
		$extra = $matches[1];
		
		if( $txt == "" )
		{
			return "[quote][/quote]";
		}
		else
		{
			$txt = trim( $txt );
			
			return "[quote{$extra}]{$txt}[/quote]";
		}
	}	
	
	
	/*-------------------------------------------------------------------------*/
	// regex_simple_quote_tag: Builds this quote tag HTML
	/*-------------------------------------------------------------------------*/
	
	/**
	* Convert simple quote tag
	*
	* @param	string	Raw text
	* @return	string	Converted text
	*/
	function regex_simple_quote_tag( $matches=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$extra   = str_replace( '&apos;', "'", $matches[1] );
		$post_id = 0;
		$date    = '';
		$name    = '';
		
		//-----------------------------------------
		// Inc..
		//-----------------------------------------
		
		$this->quote_open++;
		
		//-----------------------------------------
		// Post?
		//-----------------------------------------
		
		preg_match( "#post=([\"']|&quot;|&\#039;|&\#39;)?(\d+)([\"']|&quot;|&\#039;|&\#39;)?#", $extra, $match );
		
		if ( isset($match[2]) AND intval( $match[2] ) )
		{
			$post_id = intval( $match[2] );
		}
	
		//-----------------------------------------
		// Name?
		//-----------------------------------------
		
		preg_match( "#name=([\"']|&quot;|&\#039;|&\#39;)(.+?)([\"']|&quot;|&\#039;|&\#39;)\s?(date|post)?#is", $extra, $match );
		
		if ( isset($match[2]) AND $match[2] )
		{
			$name = $this->_make_quote_safe($match[2]);
		}
		
		//-----------------------------------------
		// Date?
		//-----------------------------------------
		
		preg_match( "#date=([\"']|&quot;|&\#039;|&\#39;)(.+?)([\"']|&quot;|&\#039;|&\#39;)#", $extra, $match );
		
		if ( isset($match[2]) AND $match[2] )
		{
			$date = $this->_make_quote_safe($match[2]);
		}
		
		//-----------------------------------------
		// Anything?
		//-----------------------------------------
		
		if ( ! $post_id AND ! $date AND ! $name )
		{
			return "<!--quoteo-->{$this->quote_html['START']}<!--quotec-->";
		}
		else
		{
			//-----------------------------------------
			// Name...
			//-----------------------------------------
			
			if ( $name or $date )
			{
				$textra = '(';
			}
			
			if ( $name )
			{
				$textra .= $name;
			}
			
			//-----------------------------------------
			// Date...
			//-----------------------------------------
			
			if ( $date )
			{
				$textra .= ' &#064; '.$date;
			}
			
			if ( $name or $date )
			{
				$textra .= ')';
			}
			
			//-----------------------------------------
			// Post...
			//-----------------------------------------
			
			if ( $post_id )
			{
				$textra .= " [snapback]{$post_id}[/snapback]";
			}
			
			$html = $this->wrap_style( 'quote', $textra );
			
			//-----------------------------------------
			// Return...
			//-----------------------------------------
		
			return "<!--quoteo(post={$post_id}:date={$date}:name={$name})-->{$html['START']}<!--quotec-->";
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// regex_close_quote: closes a quote tag
	/*-------------------------------------------------------------------------*/
	
	/**
	* Convert quote close tag
	*
	* @return	string	Converted text
	*/
	function regex_close_quote( $matches=array() )
	{
		$this->quote_closed++;
		
		return "<!--QuoteEnd-->".trim($this->quote_html['END'])."<!--QuoteEEnd-->";
	}
	
	/*-------------------------------------------------------------------------*/
	// regex_quote_tag: Builds this quote tag HTML (OLD)
	/*-------------------------------------------------------------------------*/
	
	/**
	* Convert quote tag: Main
	*
	* @param	string	Poster's name
	* @param	string	Date String
	* @param	string	Post content
	* @return	string	Converted text
	*/
	function regex_quote_tag( $matches=array() )
	{ 
		//-----------------------------------------
		// Defaults
		//-----------------------------------------
		
		$name = $matches[1];
		$date = $matches[2];
		$post = $matches[3];
		
		if ( $date != "" )
		{
			$default = "[quote=$name,$date{$post}]";
		}
		else
		{
			$default = "[quote=$name{$post}]";
		}
		
		if ( strstr( $name, '<!--c1-->' ) or strstr( $date, '<!--c1-->' ) )
		{
			//-----------------------------------------
			// Code tag detected...
			//-----------------------------------------
			
			$this->quote_error++;
		 	return $default;
		}
		
		//-----------------------------------------
		// Sort name
		//-----------------------------------------
		
		$name = str_replace( "+", "&#043;", $name );
		$name = str_replace( "-", "&#045;", $name );
		$name = str_replace( '[', "&#091;", $name );
		$name = str_replace( ']', "&#093;", $name );
		
		//-----------------------------------------
		// Inc..
		//-----------------------------------------
		
		$this->quote_open++;
		
		//-----------------------------------------
		// Quote header
		//-----------------------------------------
		
		if ($date == "")
		{
			$hextra = "($name)";
		}
		else
		{
			$hextra = "($name &#064; $date)";
		}
		
		$html = $this->wrap_style( 'quote', $hextra );
		
		//-----------------------------------------
		// Sort out extra
		//-----------------------------------------
		
		$extra = "-".$name.'+'.$date;
		
		//-----------------------------------------
		// Return...
		//-----------------------------------------
		
		return "<!--QuoteBegin".$extra."-->{$html['START']}<!--QuoteEBegin-->";
	}		
	
	/*-------------------------------------------------------------------------*/
	// _regex_build_url_manual: Checks, and builds the a href
	/*-------------------------------------------------------------------------*/
	
	/**
	* Convert manual URLs
	*
	* @param	array	Input vars
	* @return	string	Converted text
	*/
	function _regex_build_url_manual( $matches=array() )
	{
		//-----------------------------------------
		// Send off to the correct function...
		//-----------------------------------------

		return $this->regex_build_url( array( 'st'   => $matches[1],
											  'html' => $matches[2],
											  'show' => $matches[2],
											  'end'  => '' ) );
		
	}
	
	/*-------------------------------------------------------------------------*/
	// _regex_build_url_tags: Checks, and builds the a href
	/*-------------------------------------------------------------------------*/
	
	/**
	* Convert tagged URLs
	*
	* @param	array	Input vars
	* @return	string	Converted text
	*/
	function _regex_build_url_tags( $matches=array() )
	{
		//-----------------------------------------
		// Send off to the correct function...
		//-----------------------------------------
		
		return $this->regex_build_url( array( 'st'   => '',
											  'html' => $matches[1],
											  'show' => $matches[2] ? $matches[2] : $matches[1],
											  'end'  => '' ) );
		
	}
	
	/*-------------------------------------------------------------------------*/
	// regex_build_url: Checks, and builds the a href
	/*-------------------------------------------------------------------------*/
	
	/**
	* Convert URLs
	*
	* @param	array	Input vars
	* @return	string	Converted text
	*/
	function regex_build_url( $url=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$skip_it    = 0;
		$url['end'] = isset( $url['end'] ) ? $url['end'] : '';
		
		//-----------------------------------------
		// URL filtering?
		//-----------------------------------------
		
		if ( $this->ipsclass->vars['ipb_use_url_filter'] )
		{
			$list_type = $this->ipsclass->vars['ipb_url_filter_option'] == "black" ? "blacklist" : "whitelist";
			
			if ( $this->ipsclass->vars['ipb_url_'.$list_type] )
			{
				$list_values = array();
				$list_values = explode( "\n", str_replace( "\r", "", $this->ipsclass->vars['ipb_url_'.$list_type] ) );
				
				if ( count( $list_values ) )
				{
					$good_url = 0;
					
					foreach( $list_values as $my_url )
					{
						if( !trim($my_url) )
						{
							continue;
						}

						$my_url = preg_quote( $my_url, '/' );
						$my_url = str_replace( "\*", "(.*?)", $my_url );
						
						if ( $list_type == "blacklist" )
						{
							if( preg_match( '/'.$my_url.'/i', $url['html'] ) )
							{
								$this->error = 'domain_not_allowed';
							}
						}
						else
						{
							if ( preg_match( '/'.$my_url.'/i', $url['html'] ) )
							{
								$good_url = 1;
							}
						}
					}
					
					if ( ! $good_url AND $list_type == "whitelist" )
					{
						$this->error = 'domain_not_allowed';
					}
				}
			}
		}
		
		
		//-----------------------------------------
		// Make sure the last character isn't punctuation..
		// if it is, remove it and add it to the
		// end array
		//-----------------------------------------
						
		if ( preg_match( "/([\.,\?]|&#33;)$/", $url['html'], $match) )
		{
			$url['end'] .= $match[1];
			$url['html'] = preg_replace( "/([\.,\?]|&#33;)$/", "", $url['html'] );
			$url['show'] = preg_replace( "/([\.,\?]|&#33;)$/", "", $url['show'] );
		}
		
		//-----------------------------------------
		// Make sure it's not being used in a
		// closing code/quote/html or sql block
		//-----------------------------------------
		
		if ( preg_match( "/\[\/(html|quote|code|sql)/i", $url['html'] ) )
		{
			return $url['html'];
		}
		
		//-----------------------------------------
		// Make sure it's fixed if used in an
		// opening quote block
		//-----------------------------------------

		if ( preg_match( "/(\+\-\-\>)$/", $url['html'], $match) )
		{
			$url['end'] .= $match[1];
			$url['html'] = preg_replace( "/(\+\-\-\>)$/", "", $url['html'] );
			$url['show'] = preg_replace( "/(\+\-\-\>)$/", "", $url['show'] );
		}
		
		//-----------------------------------------
		// clean up the ampersands / brackets
		//-----------------------------------------
		
		$url['html'] = str_replace( "&amp;amp;", "&amp;", $url['html'] );
		$url['html'] = str_replace( "["        , "%5b"  , $url['html'] );
		$url['html'] = str_replace( "]"        , "%5d"  , $url['html'] );
		$url['html'] = str_replace( " "		   , "%20"	, $url['html'] );
		
		//-----------------------------------------
		// Make sure we don't have a JS link
		//-----------------------------------------
		
		if ( preg_match( '#javascript\:#is', preg_replace( '#\s{1,}#s', '', $url['html'] ) ) )
		{
			$url['html'] = preg_replace( "/javascript:/i", "java script&#58; ", $url['html'] );
		}
		
		if ( preg_match( '#javascript\:#is', preg_replace( '#\s{1,}#s', '', $url['show'] ) ) )
		{
			$url['show'] = preg_replace( "/javascript:/i", "java script&#58; ", $url['show'] );
		}
		
		//-----------------------------------------
		// Do we have http:// at the front?
		//-----------------------------------------
		
		if ( ! preg_match("#^(http|news|https|ftp|aim)://#", $url['html'] ) )
		{
			$url['html'] = 'http://'.$url['html'];
		}
		
		//-----------------------------------------
		// Tidy up the viewable URL
		//-----------------------------------------

		if ( preg_match( "/^<img src/i", $url['show'] ) )
		{
			$skip_it     = 1;
			$url['show'] = stripslashes($url['show']);
		}

		$url['show'] = str_replace( "&amp;amp;", "&amp;", $url['show'] );
		$url['show'] = preg_replace( "/javascript:/i", "javascript&#58; ", $url['show'] );
		
		if ( (strlen($url['show']) -58 ) < 3 )  $skip_it = 1;
		
		//-----------------------------------------
		// Make sure it's a "proper" url
		//-----------------------------------------
		
		if ( ! preg_match( "/^(http|ftp|https|news):\/\//i", $url['show'] )) $skip_it = 1;
		
		$show = $url['show'];
		
		if ($skip_it != 1)
		{
			$stripped = preg_replace( "#^(http|ftp|https|news)://(\S+)$#i", "\\2", $url['show'] );
			$uri_type = preg_replace( "#^(http|ftp|https|news)://(\S+)$#i", "\\1", $url['show'] );
			
			$show = $uri_type.'://'.substr( $stripped , 0, 35 ).'...'.substr( $stripped , -15   );
		}
		
		return ( isset($url['st']) ? $url['st'] : '' ) . "<a href=\"".$url['html']."\" target=\"_blank\">".$show."</a>" . $url['end'];
	}
	
	/*-------------------------------------------------------------------------*/
	// Remove sessions in a nice way
	/*-------------------------------------------------------------------------*/
	
	/**
	* Remove session keys from URLs
	*
	* @param	string	Start token
	* @param	string	End token
	* @return	string	Converted text
	*/
	function regex_bash_session( $matches=array() )
	{
		//-----------------------------------------
		// Case 1: index.php?s=0000        :: Return nothing (parses: index.php)
		// Case 2: index.php?s=0000&this=1 :: Return ?       (parses: index.php?this=1)
		// Case 3: index.php?this=1&s=0000 :: Return nothing (parses: index.php?this=1)
		// Case 4: index.php?t=1&s=00&y=2  :: Return &       (parses: index.php?t=1&y=2)
		//-----------------------------------------
		
		$start_tok = str_replace( '&amp;', '&', $matches[1] );
		$end_tok   = str_replace( '&amp;', '&', $matches[3]   );
		
		//1:
		if ($start_tok == '?' and $end_tok == '')
		{
			return "";
		}
		//2:
		else if ($start_tok == '?' and $end_tok == '&')
		{
			return '?';
		}
		//3:
		else if ($start_tok == '&' and $end_tok == '')
		{
			return "";
		}
		else if ($start_tok == '&' and $end_tok == '&')
		{
			return "&";
		}
		else
		{
			return $start_tok.$end_tok;
		}
	}
	
	
	/*-------------------------------------------------------------------------*/
	// Converts hex entity code to character
	/*-------------------------------------------------------------------------*/
	
	/**
	* Converts hex entity code to character
	*
	* @param	string	hex entity
	* @return	string	decimal entity
	*/
	function regex_bash_hex($hex_entity)
	{
		return html_entity_decode( "&#".hexdec( $hex_entity ).";" );
	}	
	
	/*-------------------------------------------------------------------------*/
	// regex_check_flash: Checks, and builds the <object>
	// html.
	/*-------------------------------------------------------------------------*/
	
	/**
	* Check and convert flash BBcode tags
	*
	* @param	integer	Movie width
	* @param	integer	Movie height
	* @param	string	Movie URL
	* @return	string	Converted text
	*/
	function regex_check_flash( $matches=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$width  = $matches[2];
		$height = $matches[4];
		$url    = $matches[6];
		
		$default = "[flash=".$width.",".$height."]".$url."[/flash]";
		
		//-----------------------------------------
		// Checks....
		//-----------------------------------------
		
		if ( ! $this->ipsclass->vars['allow_flash'] )
		{
			return $default;
		}
		
		if ( $width > $this->ipsclass->vars['max_w_flash'] )
		{
			$this->error = 'flash_too_big';
			return $default;
		}
		
		if ( $height > $this->ipsclass->vars['max_h_flash'] )
		{
			$this->error = 'flash_too_big';
			return $default;
		}
		
		if ( ! preg_match( "/^http:\/\/(\S+)\.swf$/i", $url) )
		{
			$this->error = 'flash_url';
			return $default;
		}
		
		return "<!--Flash $width+$height+$url--><OBJECT CLASSID='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000' WIDTH=$width HEIGHT=$height><PARAM NAME=MOVIE VALUE=$url><PARAM NAME=PLAY VALUE=TRUE><PARAM NAME=LOOP VALUE=TRUE><PARAM NAME=QUALITY VALUE=HIGH><EMBED SRC=$url WIDTH=$width HEIGHT=$height PLAY=TRUE LOOP=TRUE QUALITY=HIGH></EMBED></OBJECT><!--End Flash-->";
	}
	
	/*-------------------------------------------------------------------------*/
	// Unconvert size
	/*-------------------------------------------------------------------------*/
	
	/**
	* Convert font-size HTML back into BBCode
	*
	* @param	integer	Core size
	* @param	string	Raw text
	* @return	string	Converted text
	*/
	function unconvert_size( $matches=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$size = trim($matches[1]);
		$text = $matches[2];
		
		foreach( $this->font_sizes as $k => $v )
		{
			if( $size == $v )
			{
				$size = $k;
				break;
			}
		}
		//$size -= 7;
		
		return '[size='.$size.']'.$text.'[/size]';
	}
	
	/*-------------------------------------------------------------------------*/
	// Unconvert flash
	/*-------------------------------------------------------------------------*/
	
	/**
	* Convert flash HTML back into BBCode
	*
	* @param	string	Raw text
	* @return	string	Converted text
	*/
	function unconvert_flash($matches=array())
	{
		$f_arr = explode( "+", $matches[1] );
		
		return '[flash='.$f_arr[0].','.$f_arr[1].']'.$f_arr[2].'[/flash]';
	}
	
	/*-------------------------------------------------------------------------*/
	// Unconvert SQL
	/*-------------------------------------------------------------------------*/
	
	/**
	* Convert SQL HTML back into BBCode
	*
	* @param	string	Raw text
	* @return	string	Converted text
	*/
	function unconvert_sql($matches=array())
	{
		$sql = stripslashes($matches[2]);
		
		$sql = preg_replace( "#<span style='.+?'>#is", "", $sql );
		$sql = str_replace( "</span>"                , "", $sql );
		$sql = preg_replace( "#\s*$#"                , "", $sql );
		
		return '[sql]'.$sql.'[/sql]';
	}

	/*-------------------------------------------------------------------------*/
	// Unconvert HTML
	/*-------------------------------------------------------------------------*/
	
	/**
	* Convert HTML TAG HTML back into BBCode
	*
	* @param	string	Raw text
	* @return	string	Converted text
	*/
	function unconvert_htm($matches=array())
	{
		$html = stripslashes($matches[2]);
		
		$html = preg_replace( "#<span style='.+?'>#is", "", $html );
		$html = str_replace( "</span>"                , "", $html );
		$html = preg_replace( "#\s*$#"                , "", $html );
		
		return '[html]'.$html.'[/html]';
	}
	
	/*-------------------------------------------------------------------------*/
	// convert_emoticon:
	// replaces the text with the emoticon image
	/*-------------------------------------------------------------------------*/
	
	/**
	* Convert emoticon to image HTML
	*
	* @param	string	Emo code :)
	* @param	string	Image URL
	* @return	string	Converted text
	*/
	function convert_emoticon($code="", $image="")
	{
		if (!$code or !$image) return;
		
		//-----------------------------------------
		// Remove slashes added by preg_quote
		//-----------------------------------------
		
		$code = stripslashes($code);
		
		$this->emoticon_count++;
		
		return "<!--emo&".trim($code)."--><img src='{$this->ipsclass->vars['EMOTICONS_URL']}/$image' border='0' style='vertical-align:middle' alt='$image' /><!--endemo-->";
	}
	
	/*-------------------------------------------------------------------------*/
	// Custom array sort
	/*-------------------------------------------------------------------------*/
	
	/**
	* Custom sort operation
	*
	* @param	string	A
	* @param	string	B
	* @return	integer
	*/
	function smilie_length_sort($a, $b)
	{
		if ( strlen($a['typed']) == strlen($b['typed']) )
		{
			return 0;
		}
		return ( strlen($a['typed']) > strlen($b['typed']) ) ? -1 : 1;
	}
	
	/*-------------------------------------------------------------------------*/
	// Custom array sort
	/*-------------------------------------------------------------------------*/
	
	/**
	* Custom sort operation
	*
	* @param	string	A
	* @param	string	B
	* @return	integer
	*/
	function word_length_sort($a, $b)
	{
		if ( strlen($a['type']) == strlen($b['type']) )
		{
			return 0;
		}
		return ( strlen($a['type']) > strlen($b['type']) ) ? -1 : 1;
	}
	
	/*-------------------------------------------------------------------------*/
	// Make quote safe
	/*-------------------------------------------------------------------------*/
	
	/**
	* Make quotes safe
	*
	* @param	string	Raw text
	* @return	string	Converted text
	*/
	function _make_quote_safe( $txt='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
	
		$begin = '';
		$end   = '';
		
		//-----------------------------------------
		// Come via preg_replace_callback?
		//-----------------------------------------
		
		if ( is_array( $txt ) )
		{
			$begin = $txt[1];
			$end   = $txt[3];
			$txt   = $txt[2];
		}
		
		//-----------------------------------------
		// Sort name
		//-----------------------------------------
		
		$txt = str_replace( "+", "&#043;" , $txt );
		$txt = str_replace( "-", "&#045;" , $txt );
		$txt = str_replace( ":", "&#58;"  , $txt );
		$txt = str_replace( "[", "&#91;"  , $txt );
		$txt = str_replace( "]", "&#93;"  , $txt );
		$txt = str_replace( ")", "&#41;"  , $txt );
		$txt = str_replace( "(", "&#40;"  , $txt );
		$txt = str_replace( "'", "&#039;" , $txt );
		
		return $begin . $txt . $end;
	}
	
	/*-------------------------------------------------------------------------*/
	// Fix up quoted usernames
	/*-------------------------------------------------------------------------*/
	
	/**
	* Fix up quoted usernames
	*
	* @param	array	Quote data
	* @return	string	Converted text
	*/
	function _make_quote_name_safe( $matches=array() )
	{
		$quote = $matches[1];
		$name  = $matches[2];
		$next  = $matches[4];
		
		# Squeeze past the parser...
		$name  = str_replace( '&#39;', "&#0039;", $name );
		
		return 'name=' . $quote . $name . $quote . ' ' . $next . '='; 
	}
	
	/*-------------------------------------------------------------------------*/
	// Parse new quotes
	/*-------------------------------------------------------------------------*/
	
	/**
	* Parse new quotes
	*
	* @param	string	Quote data
	* @param	string	Raw text
	* @return	string	Converted text
	*/
	function _parse_new_quote( $matches=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
	
		$return     = array();
		$quote_data = $matches[1];
		$quote_text = $matches[2];
		
		//-----------------------------------------
		// No data?
		//-----------------------------------------
		
		if ( ! $quote_data )
		{
			return '[quote]';
		}
		else
		{
			preg_match( "#\(post=(.+?)?:date=(.+?)?:name=(.+?)?\)#", $quote_data, $match );
			
			if ( $match[3] )
			{
				$return[] = " name='{$match[3]}'";
			}
			
			if ( $match[1] )
			{
				$return[] = " post='".intval($match[1])."'";
			}
			
			if ( $match[2] )
			{
				$return[] = " date='{$match[2]}'";
			}
			
			return str_replace( '  ', ' ', '[quote' . implode( ' ', $return ).']' );
		}
	}
	
}



?>