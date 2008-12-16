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
|   > $Date: 2006-09-22 06:28:31 -0400 (Fri, 22 Sep 2006) $
|   > $Revision: 567 $
|   > $Author: matt $
+---------------------------------------------------------------------------
|
|   > BB Code LEGACY Module
|   > Module written by Matt Mecham
|   > Date started: Wednesday 9th March 2005 11:31
|
+--------------------------------------------------------------------------
*/

/**
* BBCode Parsing: Legacy sub class
*
* Sub class
*
* @package		InvisionPowerBoard
* @subpackage	BBCodeParser
* @author  	 	Matt Mecham
* @version		2.1
* @ignore
*/

/**
*
*/

/**
* BBCode Parsing: Legacy sub class
*
* Sub class
*
* @package		InvisionPowerBoard
* @subpackage	BBCodeParser
* @author  	 	Matt Mecham
* @version		2.1
* @ignore
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
		
	/*-------------------------------------------------------------------------*/
	// CONSTRUCTOR
	/*-------------------------------------------------------------------------*/
	
	function class_bbcode( )
	{
		$this->strip_quotes = $this->ipsclass->vars['strip_quotes'];
	}
	
	/*-------------------------------------------------------------------------*/
	// Manage the raw text before inserting into the DB
	/*-------------------------------------------------------------------------*/

	function pre_db_parse( $txt="" )
	{
		//-----------------------------------------
		// Remove session id's from any post
		//-----------------------------------------
		
		$txt = preg_replace( "#(\?|&amp;|;|&)s=([0-9a-zA-Z]){32}(&amp;|;|&|$)?#e", "\$this->regex_bash_session('\\1', '\\3')", $txt );
		
		//-----------------------------------------
		// convert <br> to \n
		//-----------------------------------------
		
		$txt = preg_replace( "/<br>|<br \/>/", "\n", $txt );
		
		//-----------------------------------------
		// Are we parsing bbcode?
		//-----------------------------------------
		
		if ( $this->parse_bbcode )
		{
			//-----------------------------------------
			// Do [CODE] tag
			//-----------------------------------------
			
			$txt = preg_replace( "#\[code\](.+?)\[/code\]#ies", "\$this->regex_code_tag( '\\1' )", $txt );
		
			//-----------------------------------------
			// Auto parse URLs
			//-----------------------------------------
			
			$txt = preg_replace( "#(^|\s)((http|https|news|ftp)://\w+[^\s\[\]]+)#ie"  , "\$this->regex_build_url(array('html' => '\\2', 'show' => '\\2', 'st' => '\\1'))", $txt );
		
			//-----------------------------------------
			// Do [QUOTE(name,date)] tags
			//-----------------------------------------
			
			$txt = preg_replace( "#(\[quote(.+?)?\].*\[/quote\])#ies" , "\$this->regex_parse_quotes('\\1')"  , $txt );
			
			/*-------------------------------------------------------------------------*/
			// If we are not parsing a siggie, lets have a bash
			// at the [PHP] [SQL] and [HTML] tags.
			/*-------------------------------------------------------------------------*/
			
			$txt = preg_replace( "#\[sql\](.+?)\[/sql\]#ies"    , "\$this->regex_sql_tag('\\1')"    , $txt );
			$txt = preg_replace( "#\[html\](.+?)\[/html\]#ies"  , "\$this->regex_html_tag('\\1')"   , $txt );
			
			//-----------------------------------------
			// [LIST]    [*]    [/LIST]
			//-----------------------------------------
			
			while( preg_match( "#\n?\[list\](.+?)\[/list\]\n?#ies" , $txt ) )
			{
				$txt = preg_replace( "#\n?\[list\](.+?)\[/list\]\n?#ies", "\$this->regex_list('\\1')" , $txt );
			}
			
			while( preg_match( "#\n?\[list=(a|A|i|I|1)\](.+?)\[/list\]\n?#ies" , $txt ) )
			{
				$txt = preg_replace( "#\n?\[list=(a|A|i|I|1)\](.+?)\[/list\]\n?#ies", "\$this->regex_list('\\2','\\1')" , $txt );
			}
			
			//-----------------------------------------
			// Do [IMG] [FLASH] tags
			//-----------------------------------------
			
			if ( $this->ipsclass->vars['allow_images'] )
			{
				$txt = preg_replace( "#\[img\](.+?)\[/img\]#ie"                             , "\$this->regex_check_image('\\1')"          , $txt );
				$txt = preg_replace( "#(\[flash=)(\S+?)(\,)(\S+?)(\])(\S+?)(\[\/flash\])#ie", "\$this->regex_check_flash('\\2','\\4','\\6')", $txt );
			}
		
			//-----------------------------------------
			// Start off with the easy stuff
			//-----------------------------------------
			
			$txt = preg_replace( "#\[b\](.+?)\[/b\]#is", "<b>\\1</b>", $txt );
			$txt = preg_replace( "#\[i\](.+?)\[/i\]#is", "<i>\\1</i>", $txt );
			$txt = preg_replace( "#\[u\](.+?)\[/u\]#is", "<u>\\1</u>", $txt );
			$txt = preg_replace( "#\[s\](.+?)\[/s\]#is", "<s>\\1</s>", $txt );
			
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
			
			$txt = preg_replace( "#\[email\](\S+?)\[/email\]#i"                                                                , "<a href='mailto:\\1'>\\1</a>", $txt );
			$txt = preg_replace( "#\[email\s*=\s*\&quot\;([\.\w\-]+\@[\.\w\-]+\.[\.\w\-]+)\s*\&quot\;\s*\](.*?)\[\/email\]#i"  , "<a href='mailto:\\1'>\\2</a>", $txt );
			$txt = preg_replace( "#\[email\s*=\s*([\.\w\-]+\@[\.\w\-]+\.[\w\-]+)\s*\](.*?)\[\/email\]#i"                       , "<a href='mailto:\\1'>\\2</a>", $txt );
			
			//-----------------------------------------
			// [url]http://www.index.com[/url]
			// [url=http://www.index.com]ibforums![/url]
			//-----------------------------------------
			
			$txt = preg_replace( "#\[url\](\S+?)\[/url\]#ie"                                       , "\$this->regex_build_url(array('html' => '\\1', 'show' => '\\1'))", $txt );
			$txt = preg_replace( "#\[url\s*=\s*\&quot\;\s*(\S+?)\s*\&quot\;\s*\](.*?)\[\/url\]#ie" , "\$this->regex_build_url(array('html' => '\\1', 'show' => '\\2'))", $txt );
			$txt = preg_replace( "#\[url\s*=\s*(\S+?)\s*\](.*?)\[\/url\]#ie"                       , "\$this->regex_build_url(array('html' => '\\1', 'show' => '\\2'))", $txt );
			
			//-----------------------------------------
			// font size, colour and font style
			// [font=courier]Text here[/font]
			// [size=6]Text here[/size]
			// [color=red]Text here[/color]
			//-----------------------------------------
			
			while ( preg_match( "#\[size=([^\]]+)\](.+?)\[/size\]#ies", $txt ) )
			{
				$txt = preg_replace( "#\[size=([^\]]+)\](.+?)\[/size\]#ies"    , "\$this->regex_font_attr(array('s'=>'size','1'=>'\\1','2'=>'\\2'))", $txt );
			}
			
			while ( preg_match( "#\[font=([^\]]+)\](.*?)\[/font\]#ies", $txt ) )
			{
				$txt = preg_replace( "#\[font=([^\]]+)\](.*?)\[/font\]#ies"    , "\$this->regex_font_attr(array('s'=>'font','1'=>'\\1','2'=>'\\2'))", $txt );
			}
			
			while( preg_match( "#\[color=([^\]]+)\](.+?)\[/color\]#ies", $txt ) )
			{
				$txt = preg_replace( "#\[color=([^\]]+)\](.+?)\[/color\]#ies"  , "\$this->regex_font_attr(array('s'=>'col' ,'1'=>'\\1','2'=>'\\2'))", $txt );
			}
			
			while( preg_match( "#\[background=([^\]]+)\](.+?)\[/background\]#ies", $txt ) )
			{
				$txt = preg_replace( "#\[background=([^\]]+)\](.+?)\[/background\]#ies"  , "\$this->regex_font_attr(array('s'=>'background' ,'1'=>'\\1','2'=>'\\2'))", $txt );
			}
		}
		
		//-----------------------------------------
		// Swap \n back to <br>
		//-----------------------------------------
		
		$txt = preg_replace( "/\n/", "<br />", $txt );
		
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
				foreach( $this->ipsclass->cache['emoticons'] as $row)
				{
					if ( $this->ipsclass->skin['_emodir'] && $row['emo_set'] != $this->ipsclass->skin['_emodir'] )
					{
						continue;
					}
					
					$code  = $row['typed'];
					
					if( in_array( $code, $codes_seen ) )
					{
						continue;
					}
					
					$codes_seen[] = $code;
					
					$image = $row['image'];
					
					//-----------------------------------------
					// Make safe for regex
					//-----------------------------------------
					
					$code = preg_quote($code, "/");
					
					$txt = preg_replace( "!(?<=[^\w&;/])$code(?=.\W|\W.|\W$)!ei", "\$this->convert_emoticon('$code', '$image')", $txt );
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
		
		return trim($txt);
	}
	
	/*-------------------------------------------------------------------------*/
	// This function processes the DB post before printing as output
	/*-------------------------------------------------------------------------*/
	
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
		// Custom BB code
		//-----------------------------------------
		
		if ( strstr( $t, '[/' )  )
		{ 
			$t = $this->post_db_parse_bbcode($t);
		}
		
		return $t;
	}
	
	/*-------------------------------------------------------------------------*/
	// This function processes the text before showing for editing, etc
	/*-------------------------------------------------------------------------*/
		
	function pre_edit_parse($txt="")
	{
		$txt = preg_replace( "#<!--emo&(.+?)-->.+?<!--endemo-->#", "\\1" , $txt );
		
		if ( $this->parse_bbcode )
		{
			//-----------------------------------------
			// SQL
			//-----------------------------------------
			
			$txt = preg_replace( "#<!--sql-->(.+?)<!--sql1-->(.+?)<!--sql2-->(.+?)<!--sql3-->#eis", "\$this->unconvert_sql(\"\\2\")", $txt);
			
			//-----------------------------------------
			// HTML
			//-----------------------------------------
			
			$txt = preg_replace( "#<!--html-->(.+?)<!--html1-->(.+?)<!--html2-->(.+?)<!--html3-->#e", "\$this->unconvert_htm(\"\\2\")", $txt);
			
			//-----------------------------------------
			// Images / Flash
			//-----------------------------------------
			
			$txt = preg_replace( "#<!--Flash (.+?)-->.+?<!--End Flash-->#e", "\$this->unconvert_flash('\\1')", $txt );
			$txt = preg_replace( "#<img src=[\"'](\S+?)['\"].+?".">#"      , "\[img\]\\1\[/img\]"            , $txt );
			
			//-----------------------------------------
			// Email, URLs
			//-----------------------------------------
			
			$txt = preg_replace( "#<a href=[\"']mailto:(.+?)['\"]>(.+?)</a>#"                                   , "\[email=\\1\]\\2\[/email\]"   , $txt );
			$txt = preg_replace( "#<a href=[\"'](http://|https://|ftp://|news://)?(\S+?)['\"].+?".">(.+?)</a>#" , "\[url=\\1\\2\]\\3\[/url\]"  , $txt );
			
			//-----------------------------------------
			// Quote
			//-----------------------------------------
			
			$txt = preg_replace( "#<!--QuoteBegin-->(.+?)<!--QuoteEBegin-->#"                        , '[quote]'         , $txt );
			$txt = preg_replace( "#<!--QuoteBegin-{1,2}([^>]+?)\+([^>]+?)-->(.+?)<!--QuoteEBegin-->#", "[quote=\\1,\\2]" , $txt );
			$txt = preg_replace( "#<!--QuoteBegin-{1,2}([^>]+?)\+-->(.+?)<!--QuoteEBegin-->#"        , "[quote=\\1]" , $txt );
			$txt = preg_replace( "#<!--QuoteEnd-->(.+?)<!--QuoteEEnd-->#"                            , '[/quote]'        , $txt );
			
			//-----------------------------------------
			// CODE
			//-----------------------------------------
			
			$txt = preg_replace( "#<!--c1-->(.+?)<!--ec1-->#", '[code]' , $txt );
			$txt = preg_replace( "#<!--c2-->(.+?)<!--ec2-->#", '[/code]', $txt );
			
			//-----------------------------------------
			// Easy peasy
			//-----------------------------------------
			
			$txt = preg_replace( "#<i>(.+?)</i>#is"  , "\[i\]\\1\[/i\]"  , $txt );
			$txt = preg_replace( "#<b>(.+?)</b>#is"  , "\[b\]\\1\[/b\]"  , $txt );
			$txt = preg_replace( "#<s>(.+?)</s>#is"  , "\[s\]\\1\[/s\]"  , $txt );
			$txt = preg_replace( "#<u>(.+?)</u>#is"  , "\[u\]\\1\[/u\]"  , $txt );
			
			//-----------------------------------------
			// List headache
			//-----------------------------------------
			
			$txt = preg_replace( "#(\n){0,}<ul>#" , "\\1\[list\]"  , $txt );
			$txt = preg_replace( "#(\n){0,}<ol type='(a|A|i|I|1)'>#" , "\\1\[list=\\2\]\n"  , $txt );
			$txt = preg_replace( "#(\n){0,}<li>#" , "\n\[*\]"     , $txt );
			$txt = preg_replace( "#(\n){0,}</ul>(\n){0,}#", "\n\[/list\]\\2" , $txt );
			$txt = preg_replace( "#(\n){0,}</ol>(\n){0,}#", "\n\[/list\]\\2" , $txt );
			
			//-----------------------------------------
			// SPAN
			//-----------------------------------------
			
			while ( preg_match( "#<span style=['\"]font-size:(.+?)pt;line-height:100%['\"]>(.+?)</span>#is", $txt ) )
			{
				$txt = preg_replace( "#<span style=['\"]font-size:(.+?)pt;line-height:100%['\"]>(.+?)</span>#ise" , "\$this->unconvert_size('\\1', '\\2')", $txt );
			}
			
			while ( preg_match( "#<span style=['\"]color:(.+?)['\"]>(.+?)</span>#is", $txt ) )
			{
				$txt = preg_replace( "#<span style=['\"]color:(.+?)['\"]>(.+?)</span>#is"    , "\[color=\\1\]\\2\[/color\]", $txt );
			}
			
			while ( preg_match( "#<span style=['\"]font-family:(.+?)['\"]>(.+?)</span>#is", $txt ) )
			{
				$txt = preg_replace( "#<span style=['\"]font-family:(.+?)['\"]>(.+?)</span>#is", "\[font=\\1\]\\2\[/font\]", $txt );
			}
			
			while ( preg_match( "#<span style=['\"]background-color:(.+?)['\"]>(.+?)</span>#is", $txt ) )
			{
				$txt = preg_replace( "#<span style=['\"]background-color:(.+?)['\"]>(.+?)</span>#is", "\[background=\\1\]\\2\[/font\]", $txt );
			}
			
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
		
		//-----------------------------------------
		// Clean up BR tags
		//-----------------------------------------
		
		$txt = str_replace( "<br>"  , "\n", $txt );
		$txt = str_replace( "<br />", "\n", $txt );
		
		
		return trim(stripslashes($txt));
	}
	
	
	
	
	
	
	
	
}



?>