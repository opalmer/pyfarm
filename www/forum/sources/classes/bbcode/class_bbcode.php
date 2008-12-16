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
|   > $Date: 2007-10-18 15:44:54 -0400 (Thu, 18 Oct 2007) $
|   > $Revision: 1135 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > BB Code NEWER Module
|   > Module written by Matt Mecham
|   > Date started: Wednesday 9th March 2005 11:31
|
+--------------------------------------------------------------------------
*/

/**
* BBCode Parsing: Core class
*
* This child class contains all methods
* specific to the new parsing methods
*
* @package		InvisionPowerBoard
* @subpackage	BBCodeParser
* @author  	 	Matt Mecham
* @version		2.1
* @since		2.1.0
*/

/**
*
*/

/**
* BBCode Parsing: Core class
*
* This child class contains all methods
* specific to the new parsing methods
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

class class_bbcode extends class_bbcode_core
{
	var $parse_smilies    	= 0;
	var $parse_html       	= 0;
	var $parse_bbcode     	= 0;
	var $parse_wordwrap   	= 0;
	var $parse_nl2br      	= 1;
	var $parse_custombbcode = 1;
	
	var $_image             = '';
	var $_code              = '';
	
	/*-------------------------------------------------------------------------*/
	// CONSTRUCTOR
	/*-------------------------------------------------------------------------*/
	
	function class_bbcode( )
	{
		
	}
	
	/*-------------------------------------------------------------------------*/
	// Manage the raw text before inserting into the DB
	/*-------------------------------------------------------------------------*/
	
	/**
	* Manage the raw text before inserting into the DB
	*
	* @param	string	Raw text
	* @return	string	Converted text
	*/
	function pre_db_parse( $txt="" )
	{
		//-----------------------------------------
		// Reset
		//-----------------------------------------
		
		$this->quote_open     = 0;
		$this->quote_closed   = 0;
		$this->quote_error    = 0;
		$this->error          = '';
		$this->image_count    = 0;
		$this->emoticon_count = 0;
		
		//-----------------------------------------
		// Remove session id's from any post
		//-----------------------------------------
		
		$txt = preg_replace_callback( "#(\?|&amp;|;|&)s=([0-9a-zA-Z]){32}(&amp;|;|&|$)?#", array( &$this, 'regex_bash_session' ), $txt );
		
		//-----------------------------------------
		// convert <br> to \n
		//-----------------------------------------
		
		if ( ! $this->parse_nl2br )
		{
			$txt = str_replace( "\n", "", $txt );
		}

		$txt = preg_replace( "/<br>|<br \/>/", "\n", $txt );
		
		# First we did hex, now we do url encoded
		# <script
		$txt = str_replace(  "%3C%73%63%72%69%70%74", "&lt;script" , $txt );
		# document.cookie
		$txt = str_replace( "%64%6F%63%75%6D%65%6E%74%2E%63%6F%6F%6B%69%65", "document&#46;cookie", $txt );
		
		$txt = preg_replace( "#javascript\:#is"		, "java script:"	, $txt );
		$txt = preg_replace( "#vbscript\:#is"		, "vb script:"  	, $txt );
		$txt = str_replace(  "`"					, "&#96;"       	, $txt );
		$txt = preg_replace( "#moz\-binding:#is"	, "moz binding:"	, $txt );
		$txt = str_replace(  "<script"				, "&lt;script"  	, $txt );
		
		$txt = str_replace( "&#8238;"				, ''				, $txt );

		//-----------------------------------------
		// Are we parsing bbcode?
		//-----------------------------------------
		
		if ( $this->parse_bbcode )
		{
			//-----------------------------------------
			// Do [CODE] tag
			//-----------------------------------------

			$txt = preg_replace_callback( "#\[code\](.+?)\[/code\]#is", array( &$this, 'regex_code_tag' ), $txt );

			//-----------------------------------------
			// Do [QUOTE(name,date)] tags
			//-----------------------------------------

			$txt = preg_replace_callback( "#(\[quote([^\]]+?)?\].*\[/quote\])#is" , array( &$this, 'regex_parse_quotes' ), $txt );

			// Quote changes \n to br
			$txt = preg_replace( "/<br>|<br \/>/", "\n", $txt );

			//-----------------------------------------
			// Auto parse URLs
			//-----------------------------------------
			
			$txt = preg_replace_callback( "#(^|\s|>)((http|https|news|ftp)://\w+[^\s\[\]\<]+)#i", array( &$this, '_regex_build_url_manual' ), $txt );
		
			/*-------------------------------------------------------------------------*/
			// If we are not parsing a siggie, lets have a bash
			// at the [PHP] [SQL] and [HTML] tags.
			/*-------------------------------------------------------------------------*/
			
			$txt = preg_replace_callback( "#\[sql\](.+?)\[/sql\]#is"    , array( &$this, 'regex_sql_tag'  ), $txt );
			$txt = preg_replace_callback( "#\[html\](.+?)\[/html\]#is"  , array( &$this, 'regex_html_tag' ), $txt );
			
			//-----------------------------------------
			// left, right, center
			//-----------------------------------------
			
			$txt = preg_replace( "#\[(left|right|center)\](.+?)\[/\\1\]#is"  , "<div align=\"\\1\">\\2</div>", $txt );
			
			//-----------------------------------------
			// Indent => Block quote
			//-----------------------------------------
			
			while( preg_match( "#\[indent\](.+?)\[/indent\]#is" , $txt ) )
			{
				$txt = preg_replace( "#\[indent\](.+?)\[/indent\]#is"  , "<blockquote>\\1</blockquote>", $txt );
			}
			
			//-----------------------------------------
			// [LIST]    [*]    [/LIST]
			//-----------------------------------------
			
			while( preg_match( "#\n?\[list\](.+?)\[/list\]\n?#is" , $txt ) )
			{
				$txt = preg_replace_callback( "#(\n){0,1}\[list\](.+?)\[/list\](\n){0,1}#is", array( &$this, 'regex_list' ), $txt );
			}

			while( preg_match( "#\n?\[list=(a|A|i|I|1)\](.+?)\[/list\]\n?#is" , $txt ) )
			{
				$txt = preg_replace_callback( "#(\n){0,1}\[list=(a|A|i|I|1)\](.+?)\[/list\](\n){0,1}#is", array( &$this, 'regex_list' ), $txt );
			}

			//-----------------------------------------
			// Do [IMG] [FLASH] tags
			//-----------------------------------------
			
			if ( $this->ipsclass->vars['allow_images'] )
			{
				$txt = preg_replace_callback( "#\[img\](.+?)\[/img\]#i"                             , array( &$this, 'regex_check_image' ), $txt );
				$txt = preg_replace_callback( "#(\[flash=)(\S+?)(\,)(\S+?)(\])(\S+?)(\[\/flash\])#i", array( &$this, 'regex_check_flash' ), $txt );
			}
		
			//-----------------------------------------
			// Start off with the easy stuff
			//-----------------------------------------
			
			$txt = $this->parse_simple_tag_recursively( 'b'  , 'b'     , 1, $txt );
			$txt = $this->parse_simple_tag_recursively( 'i'  , 'i'     , 1, $txt );
			$txt = $this->parse_simple_tag_recursively( 'u'  , 'u'     , 1, $txt );
			$txt = $this->parse_simple_tag_recursively( 's'  , 'strike', 1, $txt );
			$txt = $this->parse_simple_tag_recursively( 'sub', 'sub'   , 1, $txt );
			$txt = $this->parse_simple_tag_recursively( 'sup', 'sup'   , 1, $txt );
			
			//-----------------------------------------
			// (c) (r) and (tm)
			//-----------------------------------------
			
			$txt = preg_replace( "#\(c\)#i"     , "&copy;" , $txt );
			$txt = preg_replace( "#\(tm\)#i"    , "&#153;" , $txt );
			$txt = preg_replace( "#\(r\)#i"     , "&reg;"  , $txt );
			
			//-----------------------------------------
			// [email]matt@index.com[/email]
			// [email=matt@index.com]Email me[/email]
			//-----------------------------------------
			
			$txt = preg_replace( "#\[email\](\S+?)\[/email\]#i"                                                                , "<a href=\"mailto:\\1\">\\1</a>", $txt );
			$txt = preg_replace( "#\[email\s*=\s*\&quot\;([\.\w\-]+\@[\.\w\-]+\.[\.\w\-]+)\s*\&quot\;\s*\](.*?)\[\/email\]#i"  , "<a href=\"mailto:\\1\">\\2</a>", $txt );
			$txt = preg_replace( "#\[email\s*=\s*([\.\w\-]+\@[\.\w\-]+\.[\w\-]+)\s*\](.*?)\[\/email\]#i"                       , "<a href=\"mailto:\\1\">\\2</a>", $txt );
			
			//-----------------------------------------
			// [url]http://www.index.com[/url]
			// [url=http://www.index.com]ibforums![/url]
			//-----------------------------------------
			
			$txt = preg_replace_callback( "#\[url\](.*?)\[/url\]#is"                                                    , array( &$this, '_regex_build_url_tags'), $txt );
			$txt = preg_replace_callback( "#\[url\s*=\s*(?:\&quot\;|\")\s*(.*?)\s*(?:\&quot\;|\")\s*\](.*?)\[\/url\]#is", array( &$this, '_regex_build_url_tags'), $txt );
			$txt = preg_replace_callback( "#\[url\s*=\s*(.*?)\s*\](.*?)\[\/url\]#is"                                    , array( &$this, '_regex_build_url_tags'), $txt );
			
			//-----------------------------------------
			// font size, colour and font style
			// [font=courier]Text here[/font]
			// [size=6]Text here[/size]
			// [color=red]Text here[/color]
			// [background=color]Text here[/background]
			//-----------------------------------------
			
			while ( preg_match( "#\[background=([^\]]+)\](.+?)\[/background\]#is", $txt ) )
			{
				$txt = preg_replace_callback( "#\[background=([^\]]+)\](.+?)\[/background\]#is", array( &$this, '_regex_font_attr_background' ), $txt );
			}
			
			while ( preg_match( "#\[size=([^\]]+)\](.+?)\[/size\]#is", $txt ) )
			{
				$txt = preg_replace_callback( "#\[size=([^\]]+)\](.+?)\[/size\]#is"    , array( &$this, '_regex_font_attr_size' ), $txt );
			}
			
			while ( preg_match( "#\[font=([^\]]+)\](.+?)\[/font\]#is", $txt ) )
			{
				$txt = preg_replace_callback( "#\[font=([^\]]+)\](.+?)\[/font\]#is"    , array( &$this, '_regex_font_attr_font' ), $txt );
			}
			
			while( preg_match( "#\[color=([^\]]+)\](.+?)\[/color\]#is", $txt ) )
			{
				$txt = preg_replace_callback( "#\[color=([^\]]+)\](.+?)\[/color\]#is"  , array( &$this, '_regex_font_attr_color' ), $txt );
			}
		}
		
		//-----------------------------------------
		// Swap \n back to <br>
		//-----------------------------------------
		
		$txt = str_replace( "\n", "<br />", $txt );

		//-----------------------------------------
		// Unicode?
		//-----------------------------------------
		
		if ( $this->allow_unicode )
		{
			$txt = preg_replace("/&amp;#([0-9]+);/s", "&#\\1;", $txt );
		}
		
		//-----------------------------------------
		// Parse smilies (disallow smilies in siggies, or we'll have to query the DB for each post
		// and each signature when viewing a topic, not something that we really want to do.
		//-----------------------------------------
		
		if ( $this->parse_smilies )
		{
			$txt = ' '.$txt.' ';
			
			$codes_seen = array();
			
			if ( count( $this->ipsclass->cache['emoticons'] ) > 0 )
			{
				//usort( $this->ipsclass->cache['emoticons'], array( 'class_bbcode_core', 'smilie_length_sort' ) );
				
				foreach( $this->ipsclass->cache['emoticons'] as $row)
				{
					if ( is_array($this->ipsclass->skin) AND $this->ipsclass->skin['_emodir'] AND $row['emo_set'] != $this->ipsclass->skin['_emodir'] )
					{
						continue;
					}
					
					$code  = $row['typed'];
					
					if ( in_array( $code, $codes_seen ) )
					{
						continue;
					}
					
					$codes_seen[] = $code;
										
					//-----------------------------------------
					// Now, check for the html safe versions
					//-----------------------------------------	
					
					$this->_code = preg_quote( str_replace( '<', '&lt;', str_replace( '>', '&gt;', $code ) ), "#/" );	
					$this->_image = $row['image'];

					# Bug #4759
					if ( preg_match( "#" . $this->_code . "--(&gt;|>)#i", $txt ) )
					{
						continue;
					}
					
					$txt = preg_replace_callback( "#(?<=[^\w&;/\"])".$this->_code."(?=.\W|\"|\W.|\W$)#i", array( &$this, 'convert_emoticon' ), $txt );
				}
			}
			
			$txt = trim($txt);
			
			if ( $this->ipsclass->vars['max_emos'] )
			{
				if ($this->emoticon_count > $this->ipsclass->vars['max_emos'])
				{
					$this->error = 'too_many_emoticons';
				}
			}
		}
		
		//-----------------------------------------
		// Badwords
		//-----------------------------------------
		
		$txt = $this->bad_words($txt);
		
		//-----------------------------------------
		// Check BBcode
		//-----------------------------------------
		
		$txt = $this->bbcode_check($txt);
		
		return $txt;
	}
	
	/*-------------------------------------------------------------------------*/
	// This function processes the DB post before printing as output
	/*-------------------------------------------------------------------------*/
	
	/**
	* This function processes the DB post before printing as output
	*
	* @param	string	Raw text
	* @return	string	Converted text
	*/
	function pre_display_parse($t="")
	{
		if ( $this->parse_html )
		{
			$t = $this->post_db_parse_html( $t );
		}
		else
		{
			//$t = $this->my_strip_tags( $t );
		}
		
		if ( $this->parse_wordwrap > 0 )
		{
			$t = $this->my_wordwrap( $t, $this->parse_wordwrap );
		}
		
		//-----------------------------------------
		// Fix up <br /> in URLs
		//-----------------------------------------
		
		$t = preg_replace_callback( "#(<a href=[\"'])(.+?)([\"'])#is", array( &$this, '_clean_long_url' ), $t );
		
		//-----------------------------------------
		// Fix up <br /> in IMGs
		//-----------------------------------------
		
		$t = preg_replace_callback( "#(<img src=[\"'])(.+?)([\"'])#is", array( &$this, '_clean_long_url' ), $t );		
		
		//-----------------------------------------
		// Custom BB code
		//-----------------------------------------
		
		if ( strstr( $t, '[/' ) AND $this->parse_bbcode  )
		{ 
			$t = $this->post_db_parse_bbcode($t);
		}
		
		return $t;
	}
	
	/*-------------------------------------------------------------------------*/
	// This function processes the text before showing for editing, etc
	/*-------------------------------------------------------------------------*/
	
	/**
	* This function processes the text before showing for editing, etc
	*
	* @param	string	Raw text
	* @return	string	Converted text
	*/
	function pre_edit_parse($txt="")
	{
		//-----------------------------------------
		// Unconvert custom bbcode
		//-----------------------------------------
				
		$txt = $this->post_db_unparse_bbcode( $txt );
		
		//-----------------------------------------
		// Clean up BR tags
		//-----------------------------------------
		
		if ( !$this->parse_html OR $this->parse_nl2br )
		{
			$txt = str_replace( "<br>"  , "\n", $txt );
			$txt = str_replace( "<br />", "\n", $txt );
		}
		
		# Make EMO_DIR safe so the ^> regex works
		$txt = str_replace( "<#EMO_DIR#>", "&lt;#EMO_DIR&gt;", $txt );
		
		# New emo
		$txt = preg_replace( "#(\s)?<([^>]+?)emoid=\"(.+?)\"([^>]*?)".">(\s)?#is", "\\1\\3\\5", $txt );
		
		# And convert it back again...
		$txt = str_replace( "&lt;#EMO_DIR&gt;", "<#EMO_DIR#>", $txt );
		
		# Legacy
		$txt = preg_replace( "#<!--emo&(.+?)-->.+?<!--endemo-->#", "\\1" , $txt );
		
		//-----------------------------------------
		// Clean up nbsp
		//-----------------------------------------
		
		$txt = str_replace( '&nbsp;&nbsp;&nbsp;&nbsp;', "\t", $txt );
		$txt = str_replace( '&nbsp;&nbsp;'            , "  ", $txt );

		if ( $this->parse_bbcode )
		{
			//-----------------------------------------
			// SQL
			//-----------------------------------------
			
			$txt = preg_replace_callback( "#<!--sql-->(.+?)<!--sql1-->(.+?)<!--sql2-->(.+?)<!--sql3-->#is", array( &$this, 'unconvert_sql'), $txt );
			
			//-----------------------------------------
			// HTML
			//-----------------------------------------
			
			$txt = preg_replace_callback( "#<!--html-->(.+?)<!--html1-->(.+?)<!--html2-->(.+?)<!--html3-->#is", array( &$this, 'unconvert_htm'), $txt );
			
			//-----------------------------------------
			// Images / Flash
			//-----------------------------------------
		
			$txt = preg_replace_callback( "#<!--Flash (.+?)-->.+?<!--End Flash-->#", array( &$this, 'unconvert_flash'), $txt );
			$txt = preg_replace( "#<img(?:.+?)src=[\"'](\S+?)['\"][^>]+?>#is"           , "\[img\]\\1\[/img\]"            , $txt );
		
			//-----------------------------------------
			// Email, URLs
			//-----------------------------------------
			
			$txt = preg_replace( "#<a href=[\"']mailto:(.+?)['\"]>(.+?)</a>#s"                                   , "\[email=\\1\]\\2\[/email\]"   , $txt );
			$txt = preg_replace( "#<a href=[\"'](http://|https://|ftp://|news://)?(\S+?)['\"].*?".">(.+?)</a>#s" , "\[url=\"\\1\\2\"\]\\3\[/url\]"  , $txt );

			//-----------------------------------------
			// Quote
			//-----------------------------------------
			
			$txt = preg_replace( "#<!--QuoteBegin-->(.+?)<!--QuoteEBegin-->#"                        , '[quote]'         , $txt );
			$txt = preg_replace( "#<!--QuoteBegin-{1,2}([^>]+?)\+([^>]+?)-->(.+?)<!--QuoteEBegin-->#", "[quote=\\1,\\2]" , $txt );
			$txt = preg_replace( "#<!--QuoteBegin-{1,2}([^>]+?)\+-->(.+?)<!--QuoteEBegin-->#"        , "[quote=\\1]"     , $txt );
			$txt = preg_replace( "#<!--QuoteEnd-->(.+?)<!--QuoteEEnd-->#"                            , '[/quote]'        , $txt );
			
			//-----------------------------------------
			// URL Inside Quote
			//-----------------------------------------

			$txt = preg_replace( "#\[quote=(.*?)\[url(.*?)\](.+?)\[\/url\]\]#i", "[quote=\\1\\3]", str_replace( "\\", "", $txt ) );
			
			//-----------------------------------------
			// New quote
			//-----------------------------------------
			
			$txt = preg_replace_callback( "#<!--quoteo([^>]+?)?-->(.+?)<!--quotec-->#si", array( &$this, '_parse_new_quote'), $txt );
			
			//-----------------------------------------
			// left, right, center
			//-----------------------------------------
			
			$txt = preg_replace( "#<div align=\"(left|right|center)\">(.+?)</div>#is"  , "[\\1]\\2[/\\1]", $txt );
			
			//-----------------------------------------
			// Ident => Block quote
			//-----------------------------------------
			
			while( preg_match( "#<blockquote>(.+?)</blockquote>#is" , $txt ) )
			{
				$txt = preg_replace( "#<blockquote>(.+?)</blockquote>#is"  , "[indent]\\1[/indent]", $txt );
			}
			
			//-----------------------------------------
			// CODE
			//-----------------------------------------
			
			$txt = preg_replace( "#<!--c1-->(.+?)<!--ec1-->#", '[code]' , $txt );
			$txt = preg_replace( "#<!--c2-->(.+?)<!--ec2-->#", '[/code]', $txt );
			
			//-----------------------------------------
			// Start off with the easy stuff
			//-----------------------------------------
			
			$txt = $this->parse_simple_tag_recursively( 'b'     , 'b'  , 0, $txt );
			$txt = $this->parse_simple_tag_recursively( 'i'     , 'i'  , 0, $txt );
			$txt = $this->parse_simple_tag_recursively( 'u'     , 'u'  , 0, $txt );
			$txt = $this->parse_simple_tag_recursively( 'strike', 's'  , 0, $txt );
			$txt = $this->parse_simple_tag_recursively( 'sub'   , 'sub', 0, $txt );
			$txt = $this->parse_simple_tag_recursively( 'sup'   , 'sup', 0, $txt );
			
			//-----------------------------------------
			// List headache
			//-----------------------------------------
			
			$txt = preg_replace( "#(\n){0,1}<ul>#" , "\\1\[list\]"  , $txt );
			$txt = preg_replace( "#(\n){0,1}<ol>#" , "\\1\[list=1\]"  , $txt );
			$txt = preg_replace( "#(\n){0,1}<ol type=[\"'](a|A|i|I|1)[\"']>#" , "\\1\[list=\\2\]\n"  , $txt );
			$txt = preg_replace( "#(\n){0,1}<li>#" , "\n\[*\]"     , $txt );
			$txt = preg_replace( "#(\n){0,1}</ul>(\n){0,1}#", "\n\[/list\]\\2" , $txt );
			$txt = preg_replace( "#(\n){0,1}</ol>(\n){0,1}#", "\n\[/list\]\\2" , $txt );
			
			//-----------------------------------------
			// Opening style attributes
			//-----------------------------------------
			
			$txt = preg_replace( "#<!--sizeo:(.+?)-->(.+?)<!--/sizeo-->#"               , "[size=\\1]" , $txt );
			$txt = preg_replace( "#<!--coloro:(.+?)-->(.+?)<!--/coloro-->#"             , "[color=\"\\1\"]", $txt );
			$txt = preg_replace( "#<!--fonto:(.+?)-->(.+?)<!--/fonto-->#"               , "[font=\"\\1\"]" , $txt );
			$txt = preg_replace( "#<!--backgroundo:(.+?)-->(.+?)<!--/backgroundo-->#"   , "[background=\\1]" , $txt );
			
			//-----------------------------------------
			// Closing style attributes
			//-----------------------------------------
			
			$txt = preg_replace( "#<!--sizec-->(.+?)<!--/sizec-->#"            , "[/size]" , $txt );
			$txt = preg_replace( "#<!--colorc-->(.+?)<!--/colorc-->#"          , "[/color]", $txt );
			$txt = preg_replace( "#<!--fontc-->(.+?)<!--/fontc-->#"            , "[/font]" , $txt );
			$txt = preg_replace( "#<!--backgroundc-->(.+?)<!--/backgroundc-->#", "[/background]" , $txt );
			
			//-----------------------------------------
			// LEGACY SPAN TAGS
			//-----------------------------------------
			
			//-----------------------------------------
			// WYSI-Weirdness #9923464: Opera span tags
			//-----------------------------------------
					
			while ( preg_match( "#<span style='font-family: \"(.+?)\"'>(.+?)</span>#is", $txt ) )
			{
				$txt = preg_replace( "#<span style='font-family: \"(.+?)\"'>(.+?)</span>#is", "\[font=\\1\]\\2\[/font\]", $txt );
			}

			while ( preg_match( "#<span style=['\"]font-size:?(.+?)pt;?\s+?line-height:?\s+?100%['\"]>(.+?)</span>#is", $txt ) )
			{
				$txt = preg_replace_callback( "#<span style=['\"]font-size:?(.+?)pt;?\s+?line-height:?\s+?100%['\"]>(.+?)</span>#is" , array( &$this, 'unconvert_size' ), $txt );
			}
			
			while ( preg_match( "#<span style=['\"]color:?(.+?)['\"]>(.+?)</span>#is", $txt ) )
			{
				$txt = preg_replace( "#<span style=['\"]color:?(.+?)['\"]>(.+?)</span>#is"    , "\[color=" . trim("\\1") . "\]\\2\[/color\]", $txt );
			}
			
			while ( preg_match( "#<span style=['\"]font-family:?(.+?)['\"]>(.+?)</span>#is", $txt ) )
			{
				$txt = preg_replace( "#<span style=['\"]font-family:?(.+?)['\"]>(.+?)</span>#is", "\[font=\"" . trim("\\1") . "\"\]\\2\[/font\]", $txt );
			}
			
			while ( preg_match( "#<span style=['\"]background-color:?\s+?(.+?)['\"]>(.+?)</span>#is", $txt ) )
			{
				$txt = preg_replace( "#<span style=['\"]background-color:?\s+?(.+?)['\"]>(.+?)</span>#is", "\[background=\\1\]\\2\[/font\]", $txt );
			}
			
			# Legacy <strike>
			$txt = preg_replace( "#<s>(.+?)</s>#is"            , "\[s\]\\1\[/s\]"  , $txt );
			
			//-----------------------------------------
			// Tidy up the end quote stuff
			//-----------------------------------------
			
			$txt = preg_replace( "#(\[/QUOTE\])\s*?<br />\s*#si", "\\1\n", $txt );
			$txt = preg_replace( "#(\[/QUOTE\])\s*?<br>\s*#si"  , "\\1\n", $txt );
			
			$txt = preg_replace( "#<!--EDIT\|.+?\|.+?-->#" , "" , $txt );
			$txt = str_replace( "</li>", "", $txt );
			
			$txt = str_replace( "&#153;", "(tm)", $txt );
		}
		
		//-----------------------------------------
		// Parse html
		//-----------------------------------------
		
		if ( $this->parse_html )
		{
			$txt = str_replace( "&#39;", "'", $txt);
		}
		
		return trim(stripslashes($txt));
	}
	
	/*-------------------------------------------------------------------------*/
	// OVERWRITE DEFAULT: convert_emoticon:
	/*-------------------------------------------------------------------------*/
	
	/**
	* Convert emoticons: New method
	*
	* @param	string	Emo code :)
	* @param	string	Emo Image URL
	* @return	string	Converted text
	*/
	function convert_emoticon( $matches=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$code  = $this->_code;
		$image = $this->_image;
		
		if ( ! $code or ! $image )
		{
			return;
		}
		
		//-----------------------------------------
		// Remove slashes added by preg_quote
		//-----------------------------------------
		
		$code = stripslashes($code);
		
		$this->emoticon_count++;
		
		return "<img src=\"{$this->ipsclass->vars['EMOTICONS_URL']}/$image\" style=\"vertical-align:middle\" emoid=\"".trim($code)."\" border=\"0\" alt=\"$image\" />";
	}
	
	/*-------------------------------------------------------------------------*/
	// OVERWRITE DEFAULT: regex_font_attr:
	/*-------------------------------------------------------------------------*/
	
	/**
	* Convert FONT / SIZE / COLOR tags: New method
	*
	* @param	array	Input vars
	* @return	string	Converted text
	*/
	function regex_font_attr( $IN )
	{
		if ( ! is_array($IN) )
		{
			return;
		}
		
		//-----------------------------------------
		// INIT (It is!)
		//-----------------------------------------
		
		$style = $IN['1'];
		$text  = stripslashes($IN['2']);
		$type  = $IN['s'];
		
		//-----------------------------------------
		// Remove &quot;
		//-----------------------------------------
		
		$style = str_replace( '&quot;', '', $style );
		$style = str_replace( '"'     , '', $style );
		
		//-----------------------------------------
		// Make safe
		//-----------------------------------------
		
		$style = preg_replace( "/[&\(\)\.\%\[\]<>\'\"]/", "", preg_replace( "#^(.+?)(?:;|$)#", "\\1", $style ) );
		
		//-----------------------------------------
		// Size
		//-----------------------------------------
		
		if ( $type == 'size' )
		{
			$style = intval( stripslashes( $style ) );
			$real  = $this->convert_bbsize_to_realsize( $style );
			
			return "<!--sizeo:{$style}--><span style=\"font-size:".$real."pt;line-height:100%\"><!--/sizeo-->".$text."<!--sizec--></span><!--/sizec-->";
		}
		
		//-----------------------------------------
		// BACKGROUND
		//-----------------------------------------
		
		else if ($type == 'background')
		{
			$style = preg_replace( "/[^\d\w\#\s]/s", "", $style );
			return "<!--backgroundo:{$style}--><span style=\"background-color:".$style."\"><!--/backgroundo-->".$text."<!--backgroundc--></span><!--/backgroundc-->";
		}
		
		//-----------------------------------------
		// COLOR
		//-----------------------------------------
		
		else if ($type == 'col')
		{
			$style = preg_replace( "/[^\d\w\#\s]/s", "", $style );
			return "<!--coloro:{$style}--><span style=\"color:".$style."\"><!--/coloro-->".$text."<!--colorc--></span><!--/colorc-->";
		}
		
		//-----------------------------------------
		// FONT
		//-----------------------------------------
		
		else if ($type == 'font')
		{
			$style = preg_replace( "/[^\d\w\#\-\_\s]/s", "", $style );
			
			if( $this->ipsclass->browser['browser'] == 'opera' )
			{
				return "<!--fonto:{$style}--><span style='font-family: \"".$style."\"'><!--/fonto-->".$text."<!--fontc--></span><!--/fontc-->";
			}
			else
			{
				return "<!--fonto:{$style}--><span style=\"font-family:".$style."\"><!--/fonto-->".$text."<!--fontc--></span><!--/fontc-->";
			}
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Clean up URL
	/*-------------------------------------------------------------------------*/
	
	/**
	* Clean up long URLs
	*
	* @param	string	BEFORE URL
	* @param	string	URL
	* @param	string	SANS URL
	* @return	string	Converted text
	*/
	function _clean_long_url( $matches=array() )
	{
		$before = stripslashes( $matches[1] );
		$url    = stripslashes( $matches[2] );
		$after  = stripslashes( $matches[3] );
		
		return $before . str_replace( '<br />', '', str_replace( "? ", "?", $url )  ) . $after;
	}
}



?>