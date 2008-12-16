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
|   > $Date: 2007-09-11 12:37:52 -0400 (Tue, 11 Sep 2007) $
|   > $Revision: 1102 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Posting RTE Editor
|   > Module written by Matt Mecham
|   > Date started: Thursday 10th March 2005 13:47
|
+--------------------------------------------------------------------------
*/

/**
* Text Editor: RTE (WYSIWYG) Class
*
* Class for parsing RTE
*
* @package		InvisionPowerBoard
* @subpackage	TextEditor
* @author  	 	Matt Mecham
* @version		2.1
* @since		2.1.0
*/

/**
*
*/

/**
* Text Editor: RTE (WYSIWYG) Class
*
* Class for parsing RTE
*
* @package		InvisionPowerBoard
* @subpackage	TextEditor
* @author  	 	Matt Mecham
* @version		2.1
* @since		2.1.0
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class class_editor_module extends class_editor
{
	/**
	* Main IPS class object
	*
	* @var	object
	*/
	var $ipsclass;
	
	/**
	* Clean up HTML on save
	*
	* @var	integer
	*/
	var $clean_on_save = 1;
	
	/**
	* Allow HTML
	*
	* @var	integer
	*/
	var $allow_html = 0;
	
	/**
	* Debug level
	*
	* @var	integer
	*/
	var $debug      = 0;
	
	/**
	* Parsing array
	*
	* @var	array
	*/
	var $delimiters     = array( "'", '"' );
	
	/**
	* Parsing array
	*
	* @var	array
	*/
	var $non_delimiters = array( "=", ' ' );
	
	/**
	* Start tags
	*
	* @var	string
	*/
	var $start_tags;
	
	/**
	* End tags
	*
	* @var	string
	*/
	var $end_tags;
	
	/**
	* Dunno, forgotten
	*
	* @var	integer
	*/
	var $_called = 0;
	
	/*-------------------------------------------------------------------------*/
	// Process the raw post with BBCode before showing in the form
	/*-------------------------------------------------------------------------*/
	
	/**
	* Process the raw post with BBCode before showing in the form
	*
	* @param	string	Raw text
	* @return	string	Converted text
	*/
	function process_before_form( $t )
	{
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
		// Remove comments
		//-----------------------------------------
		
		$t = preg_replace( "#\<\!\-\-(.+?)\-\-\>#is", "", $t );
		
		//-----------------------------------------
		// Return
		//-----------------------------------------
		
		$t = $this->_make_rtf_safe( $t );
		
		//-----------------------------------------
		// Clean up nbsp
		//-----------------------------------------
		
		# Doing so knocks out tabs
		//$t = str_replace( '&nbsp;&nbsp;&nbsp;&nbsp;', "\t", $t );
		//$t = str_replace( '&nbsp;&nbsp;'            , "  ", $t );
		
		//-----------------------------------------
		// Clean up code tags
		//-----------------------------------------
	
		$t = preg_replace_callback( "#\[(code)\](.+?)\[/code\]#is", array( &$this, 'clean_ipb_html_code_tag' ), $t );
		
		//-----------------------------------------
		// Clean up quote tags (remove many <br />s
		//-----------------------------------------
		
		$t = preg_replace( "#(\[quote([^\]]+?)\])(<br />){1,}#is", "\\1<br />", $t );
		
		//-----------------------------------------
		// Clean up the rest of the tags
		//-----------------------------------------
		
		$t = $this->ipsclass->txt_htmlspecialchars( $t );
		
		//-----------------------------------------
		// Fix up stuff
		//-----------------------------------------
		
		$t = str_replace( "&lt;#IMG_DIR#&gt;", "<#IMG_DIR#>", $t );
		$t = str_replace( "&lt;#EMO_DIR#&gt;", "<#EMO_DIR#>", $t );
		
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
		
		$tag = $matches[1];
		$txt = $matches[2];
		
		//-----------------------------------------
		// Fix...
		//-----------------------------------------

		$txt = str_replace( "&lt;br&gt;"    , "\n"   , $txt );
		$txt = str_replace( "&lt;br /&gt;"  , "\n"   , $txt );
		$txt = str_replace( "<br>"          , "\n"   , $txt );
		$txt = str_replace( "<br />"        , "\n"   , $txt );
		$txt = str_replace( "<"             , "&lt;" , $txt );
		$txt = str_replace( ">"             , "&gt;" , $txt );
		$txt = str_replace( "&#60;"         , "&lt;" , $txt );
		$txt = str_replace( "&#62;"         , "&gt;" , $txt );
		
		return '['. $tag . ']'.nl2br($txt).'[/'. $tag .']';
	}
	
	/*-------------------------------------------------------------------------*/
	// Process the raw post with BBCode before saving
	/*-------------------------------------------------------------------------*/
	
	/**
	* Process the raw post with BBCode before saving
	*
	* @param	string	Raw text
	* @return	string	Converted text
	*/
	function process_after_form( $form_field )
	{
		return $this->_rte_html_to_bbcode( $this->ipsclass->txt_stripslashes($_POST[ $form_field ]) );
	}
	
	/*-------------------------------------------------------------------------*/
	// Covert RTE-HTML to BBCode
	/*-------------------------------------------------------------------------*/
	
	/**
	* Covert RTE-HTML to BBCode
	*
	* @param	string	Raw text
	* @return	string	Converted text
	*/
	function _rte_html_to_bbcode( $t )
	{
		if ( $this->debug )
		{
			$ot = $t;
		}
		
		//-----------------------------------------
		// Fix up spaces
		//-----------------------------------------
		
		$t = str_replace( '&nbsp;', ' ', $t );
		
		//-----------------------------------------
		// Gecko engine seems to put \r\n at edge
		// of iframe when wrapping? If so, add a 
		// space or it'll get weird later
		//-----------------------------------------
		
		if ( $this->ipsclass->browser['browser'] == 'mozilla' OR $this->ipsclass->browser['browser'] == 'gecko' )
		{
			$t = str_replace( "\r\n", " ", $t );
		}
		else
		{
			$t = str_replace( "\r\n", "", $t );
		}

		//-----------------------------------------
		// Clean up already encoded HTML
		//-----------------------------------------
		
		$t = str_replace( '&quot;', '"', $t );
		$t = str_replace( '&apos;', "'", $t );
		
		//-----------------------------------------
		// Fix up incorrectly nested urls / BBcode
		//-----------------------------------------
		
		$t = preg_replace( '#<a\s+?href=[\'"]([^>]+?)\[(.+?)[\'"](.+?)'.'>(.+?)\[\\2</a>#is', '<a href="\\1"\\3>\\4</a>[\\2', $t );
		
		//-----------------------------------------
		// Make URLs safe (prevent tag stripping)
		//-----------------------------------------
		
		$t = preg_replace_callback( '#<(a href|img src)=([\'"])([^>]+?)(\\2)#is', array( &$this, 'unhtml_url' ), $t );
		
		//-----------------------------------------
		// Make EMOids safe (prevent tag stripping)
		//-----------------------------------------
		
		$t = preg_replace_callback( '#(emoid=")(.+?)(")#is', array( &$this, 'unhtml_emoid' ), $t );
		
		//-----------------------------------------
		// WYSI-Weirdness #0: BR tags to \n
		//-----------------------------------------
	
		$t = preg_replace( "#<br.*>#isU", "\n", $t );
		
		$t = trim( $t );
		
		//-----------------------------------------
		// Remove tags we're not bothering with
		// with PHPs wonderful strip tags func
		//-----------------------------------------
		
		if ( ! $this->allow_html )
		{
			$t = strip_tags( $t, '<h1><h2><h3><h4><h5><h6><font><span><div><br><p><img><a><li><ol><ul><b><strong><em><i><u><strike><blockquote><sub><sup>' );
		}

		//-----------------------------------------
		// WYSI-Weirdness #1: Named anchors
		//-----------------------------------------
		
		$t = preg_replace( "#<a\s+?name=.+?".">(.+?)</a>#is", "\\1", $t );
		
		//-----------------------------------------
		// WYSI-Weirdness #1.5: Empty a hrefs
		//-----------------------------------------
		
		$t = preg_replace( "#<a\s+?href([^>]+)></a>#is"         , ""   , $t );
		$t = preg_replace( "#<a\s+?href=(['\"])>\\1(.+?)</a>#is", "\\1", $t );
		
		//-----------------------------------------
		// WYSI-Weirdness #1.6: Double linked links
		//-----------------------------------------
		
		$t = preg_replace( "#href=[\"']\w+://(%27|'|\"|&quot;)(.+?)\\1[\"']#is", "href=\"\\2\"", $t );
		
		//-----------------------------------------
		// WYSI-Weirdness #2: Headline tags
		//-----------------------------------------
		
		$t = preg_replace( "#<(h[0-9])>(.+?)</\\1>#is", "\n[b]\\2[/b]\n", $t );
		
		//-----------------------------------------
		// WYSI-Weirdness #2.1324613: Font tags
		//-----------------------------------------
		
		$t = preg_replace( "#<font (color|size|face)=\"([a-zA-Z0-9\s\#\-]*?)\">(\s*)</font>#is", " ", $t );

		//-----------------------------------------
		// WYSI-Weirdness #2.5: Fix up smilies
		//-----------------------------------------
		
		# Legacy Fix: Old smilies
		
		$t = str_replace( 'emoid=":""' , 'emoid="{{:&amp;quot;}}"', $t );
		$t = str_replace( 'emoid=":-""', 'emoid="{{:-&amp;quot;}}"', $t );
		
		# Make EMO_DIR safe so the ^> regex works
		$t = str_replace( "<#EMO_DIR#>", "&lt;#EMO_DIR&gt;", $t );
		
		# Parse...
		$t = preg_replace_callback( "#[ ]?<([^>]+?)emoid=\"(.+?)\"([^>]+?)?".">[ ]?#is", array( &$this, 'html_emoid' ), $t );
		
		# And convert it back again...
		$t = str_replace( "&lt;#EMO_DIR&gt;", "<#EMO_DIR#>", $t );
		
		# Convert back
		$t = str_replace( "{{:&amp;quot;}}" , ':"' , $t );
		$t = str_replace( "{{:-&amp;quot;}}", ':-"', $t );
		
		//-----------------------------------------
		// WYSI-Weirdness #3: Image tags
		//-----------------------------------------
		
		$t = preg_replace( "#<img.+?src=[\"'](.+?)[\"']([^>]+?)?".">#is", "[img]\\1[/img]", $t );
		
		//-----------------------------------------
		// WYSI-Weirdness #4: Linked URL tags
		//-----------------------------------------
		
		$t = preg_replace( "#\[url=(\"|'|&quot;)<a\s+?href=[\"'](.*)/??['\"]\\2/??</a>#is", "[url=\\1\\2", $t );
		
		//-----------------------------------------
		// WYSI-Weirdness: Make relative links full links
		//-----------------------------------------
		
		$t = preg_replace( "#\[img\](/)?style_(emoticons|images)#i", '[img]' . $this->ipsclass->vars['board_url'] . '/style_' . '\\2', $t );
		
		//-----------------------------------------
		// Now, recursively parse the other tags
		// to make sure we get the nested ones
		//-----------------------------------------
		
		$t = $this->_recurse_and_parse( 'b'          , $t, "_parse_simple_tag", 'b' );
		$t = $this->_recurse_and_parse( 'u'          , $t, "_parse_simple_tag", 'u' );
		$t = $this->_recurse_and_parse( 'strong'     , $t, "_parse_simple_tag", 'b' );
		$t = $this->_recurse_and_parse( 'i'          , $t, "_parse_simple_tag", 'i' );
		$t = $this->_recurse_and_parse( 'em'         , $t, "_parse_simple_tag", 'i' );
		$t = $this->_recurse_and_parse( 'strike'     , $t, "_parse_simple_tag", 's' );
		$t = $this->_recurse_and_parse( 'blockquote' , $t, "_parse_simple_tag", 'indent' );
		
		$t = $this->_recurse_and_parse( 'sup' 		 , $t, "_parse_simple_tag", 'sup' );
		$t = $this->_recurse_and_parse( 'sub' 		 , $t, "_parse_simple_tag", 'sub' );

		//-----------------------------------------
		// More complex tags
		//-----------------------------------------
		
		$t = $this->_recurse_and_parse( 'a'          , $t, "_parse_anchor_tag" );
		$t = $this->_recurse_and_parse( 'font'       , $t, "_parse_font_tag" );
		$t = $this->_recurse_and_parse( 'div'        , $t, "_parse_div_tag" );
		$t = $this->_recurse_and_parse( 'span'       , $t, "_parse_span_tag" );
		$t = $this->_recurse_and_parse( 'p'          , $t, "_parse_paragraph_tag" );
		
		//-----------------------------------------
		// Lists
		//-----------------------------------------
		
		$t = $this->_recurse_and_parse( 'ol'         , $t, "_parse_list_tag" );
		$t = $this->_recurse_and_parse( 'ul'         , $t, "_parse_list_tag" );
		
		//-----------------------------------------
		// WYSI-Weirdness #6: Fix up para tags
		//-----------------------------------------
		
		$t = preg_replace( "#<p.*>#isU", "\n\n", $t );
		
		//-----------------------------------------
		// WYSI-Weirdness #7: Random junk
		//-----------------------------------------
		
		$t = preg_replace( "#(<a>|</a>|</li>)#is", "", $t );
		
		//-----------------------------------------
		// WYSI-Weirdness #8: Fix up list stuff
		//-----------------------------------------
		
		$t = preg_replace( '#<li>(.*)((?=<li>)|</li>)#is', '\\1', $t );
		
		//-----------------------------------------
		// WYSI-Weirdness #9: Convert rest to HTML
		//-----------------------------------------
		
		$t = str_replace(  '&lt;' , '<', $t );
		$t = str_replace(  '&gt;' , '>', $t );
		$t = str_replace(  '&amp;', '&', $t );
		$t = preg_replace( '#&amp;(quot|lt|gt);#', '&\\1;', $t );
		
		//-----------------------------------------
		// WYSI-Weirdness #10: Remove useless tags
		//-----------------------------------------
		
		while( preg_match( "#\[(url|img|b|u|i|s|email|list|indent|right|left|center)\]\[/\\1\]#is", $t ) )
		{
			$t = preg_replace( "#\[(url|img|b|u|i|s|email|list|indent|right|left|center)\]\[/\\1\]#is", "", $t );
		}
		
		//-----------------------------------------
		// WYSI-Weirdness #11: Opera crap
		//-----------------------------------------
		
		$t = preg_replace( "#\[(font|size|color)\]=[\"']([^\"']+?)[\"']\]\[/\\1\]#is", "", $t );
		
		//-----------------------------------------
		// WYSI-Weirdness #11: No domain in FF?
		//-----------------------------------------	
		
		$t = preg_replace( "#(http|https):\/\/index.php(.*?)#is", $this->ipsclass->vars['board_url'].'/index.php\\2', $t );	
		$t = preg_replace( "#\[url=['\"]index.php(.*?)[\"']#is", "[url=\"".$this->ipsclass->vars['board_url'].'/index.php\\1"', $t );	
		
		//-----------------------------------------
		// Now call the santize routine to make
		// html and nasties safe. VITAL!!
		//-----------------------------------------
		
		$t = $this->_clean_post( $t );
		
		//-----------------------------------------
		// Debug?
		//-----------------------------------------
		
		if ( $this->debug )
		{
			print "<hr>";
			print nl2br(htmlspecialchars($ot));
			print "<hr>";
			print nl2br($t);
			print "<hr>";
			exit();
		}
		
		//-----------------------------------------
		// Done
		//-----------------------------------------
		
		return $t;
	}
	
	/*-------------------------------------------------------------------------*/
	// Parse list tags
	/*-------------------------------------------------------------------------*/
	
	/**
	* RTE: Parse List tag
	*
	* @param	string	Tag
	* @param	string	Text between opening and closing tag
	* @param	string	Opening tag complete
	* @param	string	Parse tag
	* @return	string	Converted text
	*/
	function _parse_list_tag( $tag, $between_text, $opening_tag, $parse_tag )
	{
		$list_type = trim( preg_replace( '#"?list-style-type:\s+?([\d\w\_\-]+);?"?#si', '\\1', $this->_get_value_of_option( 'style', $opening_tag ) ) );
		
		//-----------------------------------------
		// Set up a default...
		//-----------------------------------------
		
		if ( ! $list_type and $tag == 'ol' )
		{
			$list_type = 'decimal';
		}
		
		//-----------------------------------------
		// Tricky regex to clean all list items
		//-----------------------------------------
		
		$between_text = preg_replace('#<li>((.(?!</li))*)(?=</?ul|</?ol|\[list|<li|\[/list)#siU', '<li>\\1</li>', $between_text);
		
		$between_text = $this->_recurse_and_parse( 'li', $between_text, "_parse_listelement_tag" );
		
		$allowed_types = array( 'upper-alpha' => 'A',
								'upper-roman' => 'I',
								'lower-alpha' => 'a',
								'lower-roman' => 'i',
								'decimal'     => '1' );
		
		if ( ! $allowed_types[ $list_type ] )
		{
			$open_tag = '[list]';
		}
		else
		{
			$open_tag = '[list='.$allowed_types[ $list_type ].']';
		}
		
		return $open_tag . $this->_recurse_and_parse( $tag, $between_text, '_parse_list_tag' ) . '[/list]';
	}
	
	/*-------------------------------------------------------------------------*/
	// Parse anchor tags
	/*-------------------------------------------------------------------------*/
	
	/**
	* RTE: Parse List Element tag
	*
	* @param	string	Tag
	* @param	string	Text between opening and closing tag
	* @param	string	Opening tag complete
	* @param	string	Parse tag
	* @return	string	Converted text
	*/
	function _parse_listelement_tag( $tag, $between_text, $opening_tag, $parse_tag )
	{
		return '[*]' . rtrim( $between_text );
	}
	
	/*-------------------------------------------------------------------------*/
	// Parse anchor tags
	/*-------------------------------------------------------------------------*/
	
	/**
	* RTE: Parse paragraph tags
	*
	* @param	string	Tag
	* @param	string	Text between opening and closing tag
	* @param	string	Opening tag complete
	* @param	string	Parse tag
	* @return	string	Converted text
	*/
	function _parse_paragraph_tag( $tag, $between_text, $opening_tag, $parse_tag )
	{
		//-----------------------------------------
		// Reset local start tags
		//-----------------------------------------
		
		$start_tags = "";
		$end_tags   = "";
		
		//-----------------------------------------
		// Check for inline style moz may have added
		//-----------------------------------------
		
		$this->_parse_style_attributes( $opening_tag, $start_tags, $end_tags );
		
		//-----------------------------------------
		// Now parse align and style (if any)
		//-----------------------------------------
		
		$align = $this->_get_value_of_option( 'align', $opening_tag );
		$style = $this->_get_value_of_option( 'style', $opening_tag );
		
		if ( $align == 'center' )
		{
			$start_tags .= '[center]';
			$end_tags   .= '[/center]';
		}
		else if ( $align == 'left' )
		{
			$start_tags .= '[left]';
			$end_tags   .= '[/left]';
		}
		else if ( $align == 'right' )
		{
			$start_tags .= '[right]';
			$end_tags   .= '[/right]';
		}
		else
		{
			# No align? Make paragraph
			$end_tags .= "\n";
		}
		
		$end_tags .= "\n";
		
		return $start_tags . $this->_recurse_and_parse( 'p', $between_text, '_parse_paragraph_tag' ) . $end_tags;
	}
	
	/*-------------------------------------------------------------------------*/
	// Parse anchor tags
	/*-------------------------------------------------------------------------*/
	
	/**
	* RTE: Parse Span tag
	*
	* @param	string	Tag
	* @param	string	Text between opening and closing tag
	* @param	string	Opening tag complete
	* @param	string	Parse tag
	* @return	string	Converted text
	*/
	function _parse_span_tag( $tag, $between_text, $opening_tag, $parse_tag )
	{
		$start_tags = "";
		$end_tags   = "";
		
		//-----------------------------------------
		// Check for inline style moz may have added
		//-----------------------------------------

		$this->_parse_style_attributes( $opening_tag, $start_tags, $end_tags );
		
		return $start_tags . $this->_recurse_and_parse( 'span', $between_text, '_parse_span_tag' ) . $end_tags;
	}
	
	/*-------------------------------------------------------------------------*/
	// Parse anchor tags
	/*-------------------------------------------------------------------------*/
	
	/**
	* RTE: Parse DIV tag
	*
	* @param	string	Tag
	* @param	string	Text between opening and closing tag
	* @param	string	Opening tag complete
	* @param	string	Parse tag
	* @return	string	Converted text
	*/
	function _parse_div_tag( $tag, $between_text, $opening_tag, $parse_tag )
	{
		//-----------------------------------------
		// Reset local start tags
		//-----------------------------------------
		
		$start_tags = "";
		$end_tags   = "";
		
		//-----------------------------------------
		// #DEBUG
		//-----------------------------------------
		
		if ( $this->debug == 2 )
		{
			print "<b><span style='color:red'>DIV FIRED</b></span><br />Start tags: {$this->start_tags}<br />End tags: {$this->end_tags}<br />Between text:<br />".htmlspecialchars($between_text)."<hr />";
		}
		
		//-----------------------------------------
		// Check for inline style moz may have added
		//-----------------------------------------
		
		$this->_parse_style_attributes( $opening_tag, $start_tags, $end_tags );
		
		//-----------------------------------------
		// Now parse align (if any)
		//-----------------------------------------
		
		$align = $this->_get_value_of_option( 'align', $opening_tag );
		
		if ( $align == 'center' )
		{
			$start_tags .= '[center]';
			$end_tags   .= '[/center]';
		}
		else if ( $align == 'left' )
		{
			$start_tags .= '[left]';
			$end_tags   .= '[/left]';
		}
		else if ( $align == 'right' )
		{
			$start_tags .= '[right]';
			$end_tags   .= '[/right]';
		}
		
		//$end_tags .= "\n";
		
		//-----------------------------------------
		// Get recursive text
		//-----------------------------------------
		
		$final = $this->_recurse_and_parse( 'div', $between_text, '_parse_div_tag' );
		
		//-----------------------------------------
		// #DEBUG
		//-----------------------------------------
		
		if ( $this->debug == 2 )
		{
			print "\n<hr><b style='color:green'>FINISHED</b><br/ >".$start_tags . $final . $end_tags."<hr>";
		}
		
		//-----------------------------------------
		// Now return
		//-----------------------------------------
		
		return $start_tags . $final . $end_tags;
	}
	
	/*-------------------------------------------------------------------------*/
	// Parse style attributes
	/*-------------------------------------------------------------------------*/
	
	/**
	* RTE: Parse style attributes (color, font, size, b, i..etc)
	*
	* @param	string	Opening tag complete
	* @return	string	Converted text
	*/
	function _parse_style_attributes( $opening_tag, &$start_tags, &$end_tags )
	{
		$style_list = array(
							array('tag' => 'color' , 'rx' => '(?<!\w)color:\s*([^;]+);?'  , 'match' => 1),
							array('tag' => 'font'  , 'rx' => 'font-family:\s*([^;]+);?'   , 'match' => 1),
							array('tag' => 'size'  , 'rx' => 'font-size:\s*([\d]+);?'     , 'match' => 1),
							array('tag' => 'b'     , 'rx' => 'font-weight:\s*(bold);?'),
							array('tag' => 'i'     , 'rx' => 'font-style:\s*(italic);?'),
							array('tag' => 'u'     , 'rx' => 'text-decoration:\s*(underline);?'),
							array('tag' => 'left'  , 'rx' => 'text-align:\s*(left);?'),
							array('tag' => 'center', 'rx' => 'text-align:\s*(center);?'),
							array('tag' => 'right' , 'rx' => 'text-align:\s*(right);?'),
						  );
		
		//-----------------------------------------
		// get style option
		//-----------------------------------------
		
		$style = $this->_get_value_of_option( 'style', $opening_tag );

		//-----------------------------------------
		// Convert RGB to hex
		//-----------------------------------------
		
		$style = preg_replace_callback( '#(?<!\w)color:\s+?rgb\((\d+,\s+?\d+,\s+?\d+)\)(;?)#i', array( &$this, '_rgb_to_hex' ), $style );
		
		//-----------------------------------------
		// Pick through possible styles
		//-----------------------------------------
		
		foreach( $style_list as $data )
		{
			if ( preg_match( '#'.$data['rx'].'#i', $style, $match ) )
			{
				if ( $data['match'] )
				{
					if ( $data['tag'] != 'size' )
					{
						$start_tags .= "[{$data['tag']}={$match[$data['match']]}]";
					}
					else
					{
						$start_tags .= "[{$data['tag']}=" . $this->convert_realsize_to_bbsize($match[$data['match']]) ."]";
					}
				}
				else
				{
					$start_tags .= "[{$data['tag']}]";
				}
				
				$end_tags = "[/{$data['tag']}]" . $end_tags;
			}
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Parse font tags
	/*-------------------------------------------------------------------------*/
	
	/**
	* RTE: Parse FONT tag
	*
	* @param	string	Tag
	* @param	string	Text between opening and closing tag
	* @param	string	Opening tag complete
	* @param	string	Parse tag
	* @return	string	Converted text
	*/
	function _parse_font_tag( $tag, $between_text, $opening_tag, $parse_tag )
	{
		$font_tags  = array( 'font' => 'face', 'size' => 'size', 'color' => 'color' );
		$start_tags = "";
		$end_tags   = "";
		
		//-----------------------------------------
		// Check for attributes
		//-----------------------------------------
		
		foreach( $font_tags as $bbcode => $string )
		{
			$option = $this->_get_value_of_option( $string, $opening_tag );
			
			if ( $option )
			{
				$start_tags .= "[$bbcode=\"$option\"]";
				$end_tags    = "[/$bbcode]" . $end_tags;
				
				if ( $this->debug == 2 )
				{
					print "<br />Got bbcode=$bbcode / opening_tag=$opening_tag";
					print "<br />- Adding [$bbcode=\"$option\"] [/$bbcode]";
					print "<br />-- start tags now: {$start_tags}";
					print "<br />-- end tags now: {$end_tags}";
				}
			}
		}
		
		//-----------------------------------------
		// Now check for inline style moz may have
		// added
		//-----------------------------------------
		
		$this->_parse_style_attributes( $opening_tag, $start_tags, $end_tags );
		
		return $start_tags . $this->_recurse_and_parse( 'font', $between_text, '_parse_font_tag' ) . $end_tags;
	}
	
	/*-------------------------------------------------------------------------*/
	// Parse anchor tags
	/*-------------------------------------------------------------------------*/
	
	/**
	* RTE: Simple tags
	*
	* @param	string	Tag
	* @param	string	Text between opening and closing tag
	* @param	string	Opening tag complete
	* @param	string	Parse tag
	* @return	string	Converted text
	*/
	function _parse_simple_tag( $tag, $between_text, $opening_tag, $parse_tag )
	{
		if ( ! $parse_tag )
		{
			$parse_tag = $tag;
		}
		
		return "[$parse_tag]".$this->_recurse_and_parse( $tag, $between_text, '_parse_simple_tag', $parse_tag )."[/$parse_tag]";
	}
	
	/*-------------------------------------------------------------------------*/
	// Parse simple tag
	/*-------------------------------------------------------------------------*/
	
	/**
	* RTE: Parse A HREF tag
	*
	* @param	string	Tag
	* @param	string	Text between opening and closing tag
	* @param	string	Opening tag complete
	* @param	string	Parse tag
	* @return	string	Converted text
	*/
	function _parse_anchor_tag( $tag, $between_text, $opening_tag, $parse_tag='' )
	{
		$mytag = 'url';
		$href  = $this->_get_value_of_option( 'href', $opening_tag );
		
		$href  = str_replace( '<', '&lt;', $href );
		$href  = str_replace( '>', '&gt;', $href );
		$href  = str_replace( ' ', '%20' , $href );
		
		if ( preg_match( '#^mailto\:#is', $href ) )
		{
			$mytag = 'email';
			$href  = str_replace( "mailto:", "", $href );
		}
		
		return "[$mytag=\"$href\"]".$this->_recurse_and_parse( $tag, $between_text, '_parse_anchor_tag', $parse_tag )."[/$mytag]";
	}
	
	/*-------------------------------------------------------------------------*/
	// Recursively parse tags
	/*-------------------------------------------------------------------------*/
	
	/**
	* RTE: Recursively parse tags
	*
	* @param	string	Tag
	* @param	string	Text between opening and closing tag
	* @param	string	Callback Function
	* @param	string	Parse tag
	* @return	string	Converted text
	*/
	function _recurse_and_parse( $tag, $text, $function, $parse_tag='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$tag              = strtolower($tag);
		$open_tag         = "<".$tag;
		$open_tag_len     = strlen($open_tag);
		$close_tag        = "</".$tag.">";
		$close_tag_len    = strlen($close_tag);
		$start_search_pos = 0;
		$tag_begin_loc    = 1;
		
		//-----------------------------------------
		// Start the loop
		//-----------------------------------------
		
		while ( $tag_begin_loc !== FALSE )
		{
			$lowtext       = strtolower($text);
			$tag_begin_loc = @strpos($lowtext, $open_tag, $start_search_pos);
			$lentext       = strlen($text);
			$quoted        = '';
			$got           = FALSE;
			$tag_end_loc   = FALSE;
			
			//-----------------------------------------
			// No opening tag? Break
			//-----------------------------------------
		
			if ( $tag_begin_loc === FALSE )
			{
				break;
			}
			
			//-----------------------------------------
			// Pick through text looking for delims
			//-----------------------------------------
			
			for ( $end_opt = $tag_begin_loc; $end_opt <= $lentext; $end_opt++ )
			{
				$chr = $text{$end_opt};
				
				//-----------------------------------------
				// We're now in a quote
				//-----------------------------------------
				
				if ( ( in_array( $chr, $this->delimiters ) ) AND $quoted == '' )
				{
					$quoted = $chr;
				}
				
				//-----------------------------------------
				// We're not in a quote any more
				//-----------------------------------------
				
				else if ( ( in_array( $chr, $this->delimiters ) ) AND $quoted == $chr )
				{
					$quoted = '';
				}
				
				//-----------------------------------------
				// Found the closing bracket of the open tag
				//-----------------------------------------
				
				else if ( $chr == '>' AND ! $quoted )
				{
					$got = TRUE;
					break;
				}
				
				else if ( ( in_array( $chr, $this->non_delimiters ) ) AND ! $tag_end_loc )
				{
					$tag_end_loc = $end_opt;
				}
			}
			
			//-----------------------------------------
			// Not got the complete tag?
			//-----------------------------------------
			
			if ( ! $got )
			{
				break;
			}
			
			//-----------------------------------------
			// Not got a tag end location?
			//-----------------------------------------
			
			if ( ! $tag_end_loc )
			{
				$tag_end_loc = $end_opt;
			}
			
			//-----------------------------------------
			// Extract tag options...
			//-----------------------------------------
			
			$tag_opts        = substr( $text   , $tag_begin_loc + $open_tag_len, $end_opt - ($tag_begin_loc + $open_tag_len) );
			$actual_tag_name = substr( $lowtext, $tag_begin_loc + 1            , ( $tag_end_loc - $tag_begin_loc ) - 1 );
			
			//-----------------------------------------
			// Check against actual tag name...
			//-----------------------------------------
			
			if ( $actual_tag_name != $tag )
			{
				$start_search_pos = $end_opt;
				continue;
			}
	
			//-----------------------------------------
			// Now find the end tag location
			//-----------------------------------------
			
			$tag_end_loc = strpos( $lowtext, $close_tag, $end_opt );
			
			//-----------------------------------------
			// Not got one? Break!
			//-----------------------------------------
			
			if ( $tag_end_loc === FALSE )
			{
				break;
			}
	
			//-----------------------------------------
			// Check for nested tags
			//-----------------------------------------
			
			$nest_open_pos = strpos($lowtext, $open_tag, $end_opt);
			
			while ( $nest_open_pos !== FALSE AND $tag_end_loc !== FALSE )
			{
				//-----------------------------------------
				// It's not actually nested
				//-----------------------------------------
				
				if ( $nest_open_pos > $tag_end_loc )
				{
					break;
				}
				
				if ( $this->debug == 2)
				{
					print "\n\n<hr>( ".htmlspecialchars($open_tag)." ) NEST FOUND</hr>\n\n";
				}
				
				$tag_end_loc   = strpos($lowtext, $close_tag, $tag_end_loc   + $close_tag_len);
				$nest_open_pos = strpos($lowtext, $open_tag , $nest_open_pos + $open_tag_len );
			}
			
			//-----------------------------------------
			// Make sure we have an end location
			//-----------------------------------------
			
			if ( $tag_end_loc === FALSE )
			{
				$start_search_pos = $end_opt;
				continue;
			}
	
			$this_text_begin  = $end_opt + 1;
			$between_text     = substr($text, $this_text_begin, $tag_end_loc - $this_text_begin);
			$offset           = $tag_end_loc + $close_tag_len - $tag_begin_loc;
			
			//-----------------------------------------
			// Pass to function
			//-----------------------------------------
			
			$final_text       = $this->$function($tag, $between_text, $tag_opts, $parse_tag);
			
			//-----------------------------------------
			// #DEBUG
			//-----------------------------------------
			
			if ( $this->debug == 2)
			{
				print "<hr><b>REPLACED {$function}($tag, ..., $tag_opts):</b><br />".htmlspecialchars(substr($text, $tag_begin_loc, $offset))."<br /><b>WITH:</b><br />".htmlspecialchars($final_text)."<hr>NEXT ITERATION";
			}
				
			//-----------------------------------------
			// Swap text
			//-----------------------------------------
			
			$text             = substr_replace($text, $final_text, $tag_begin_loc, $offset);
			$start_search_pos = $tag_begin_loc + strlen($final_text);
		} 
	
		return $text;
	}

	/**
	* RTE: parse recursively: Quick method
	*
	* @param	string	Tag
	* @param	string	Text between opening and closing tag
	* @param	string	Callback Function
	* @param	string	Parse tag
	* @return	string	Converted text
	*/
	function _recurse_and_parse_quick( $tag, $text, $function, $parse_tag='' )
	{
		//-----------------------------------------
		// Set up
		//-----------------------------------------
		
		$tag       = strtolower( $tag );
		$open_tag  = "<".$tag;
		$close_tag = "</".$tag.">";
		$lowtext   = strtolower( $text );
		
		//-----------------------------------------
		// Return if no content or no opening tag
		//-----------------------------------------
		
		if ( ( ! $text ) OR ( ! strstr( $lowtext, $open_tag ) ) )
		{
			return $text;
		}
		
		//-----------------------------------------
		// Go loopy
		//-----------------------------------------
		
		while( strstr( $lowtext, $open_tag ) !== FALSE )
		{
			//-----------------------------------------
			// Quick check
			//-----------------------------------------
			
			if ( ! strstr( $lowtext, $open_tag ) )
			{
				break;
			}
			
			if ( ! strstr( $lowtext, $close_tag ) )
			{
				break;
			}
			
			//-----------------------------------------
			// Try and get a match. First tag to the
			// very last tag
			//-----------------------------------------
			
			preg_match( "#($open_tag(?:.+?)?".">)(.+?)$close_tag#is", $text, $match );
			
			if ( $this->debug == 2 )
			{
				print "<hr><pre>";
				print_r( array_map('htmlspecialchars',$match) );
				print "</pre></hr>";
			}
			
			$entire_match = $match[0];
			$opening_tag  = $match[1];
			$between_text = $match[2];
			
			if ( ! $entire_match )
			{
				break;
			}
			
			//-----------------------------------------
			// Look for nested tags
			//-----------------------------------------
			
			if ( strstr( strtolower( $between_text ), $open_tag ) !== FALSE )
			{
				if ( $this->debug == 2)
				{
					print "\n\n<hr>( ".htmlspecialchars($open_tag)." ) NEST FOUND</hr>\n\n";
				}
				
				//-----------------------------------------
				// How many opening tags?
				//-----------------------------------------
				
				$open_tag_count  = substr_count( $between_text, $open_tag );
				$close_tag_count = substr_count( $between_text, $close_tag );
				$chopped_text    = $entire_match;
				$tmp_text        = preg_replace( "#(^|.+?)".preg_quote( $entire_match, '#' )."#s", '', $text );
				
				//-----------------------------------------
				// Okay, pick through the text finding as
				// many closing tags as we have opening tags
				//-----------------------------------------
				
				while( ( $open_tag_count != $close_tag_count ) OR $tmp_text != '' )
				{
					preg_match( "#(.+?)$close_tag#is", $tmp_text, $match );
					
					if ( $this->debug == 2)
					{
						print "<hr>IN RECURSE TMP TEXT: ".htmlspecialchars($tmp_text)."<br>Open tags: {$open_tag_count}<br />Closed tags: {$close_tag_count}<hr>";
					}
			
					if ( $match[1] )
					{
						$open_tag_count  += intval( substr_count( $match[1], $open_tag ) );
						$close_tag_count++;
						
						$chopped_text .= $match[0];
						$tmp_text      = preg_replace( "#(^|.+?)".preg_quote( $match[0], '#' )."#s", '', $tmp_text );
					}
					else
					{
						// Cannot find, somethings wrong so break
						break;
					}
				}
				
				//-----------------------------------------
				// Piece back together the 'between_text'
				//-----------------------------------------
				
				preg_match( "#$open_tag(?:.+?)?".">(.*)$close_tag#is", $chopped_text, $newmatch );
				
				$between_text = $newmatch[1];
				$entire_match = $chopped_text;
			}
			
			//-----------------------------------------
			// Pass to handler
			//-----------------------------------------
			
			if ( $between_text )
			{
				$newtext = $this->$function( $tag, $between_text, $opening_tag, $parse_tag );
				//$text    = preg_replace( "#".preg_quote( $entire_match, '#' )."#", $newtext, $text );
				$text    = str_replace( $entire_match, $newtext, $text );
				
				if ( $this->debug == 2)
				{
					print "<hr><b>REPLACED:</b><br />".htmlspecialchars($entire_match)."<br /><b>WITH:</b><br />".htmlspecialchars($newtext)."<br /><b>TEXT IS NOW</b>:<br />".htmlspecialchars($text)."<hr>";
				}
			}
		}
		
		if ( $this->debug == 2)
		{
			print "<hr>NEW TEXT: ".htmlspecialchars($text)."\n\n<br /><span style='color:red'>ALL DONE - RETURNING \$text now....</span><hr>";
		}
			
		return $text;
	}
	
	/*-------------------------------------------------------------------------*/
	// Get value of option
	/*-------------------------------------------------------------------------*/
	
	/**
	* RTE: Extract option HTML
	*
	* @param	string	Option
	* @param	string	Text
	* @return	string	Converted text
	*/
	function _get_value_of_option( $option, $text )
	{
		if( $option == 'face' )
		{
			// Bad font face, bad
			preg_match( "#$option(\s+?)?\=(\s+?)?[\"']?(.+?)([\"']|$|color|size|>)#is", $text, $matches );
		}
		else
		{
			preg_match( "#$option(\s*?)?\=(\s*?)?[\"']?(.+?)([\"']|$|\s|>)#is", $text, $matches );
		}

		//preg_match( "#$option(\s+?)?\=(\s+?)?[\"']?(.+?)([\"']|$|>|\s)#is", $text, $matches );
		return isset($matches[3]) ? trim( $matches[3] ) : '';
	}
	
}


?>