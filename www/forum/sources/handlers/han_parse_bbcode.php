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
|   > Handler for BBCode parsing
|   > Module written by Matt Mecham
|   > Date started: Wednesday 9th March 2005 11:03
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class parse_bbcode
{
	# Global
	var $ipsclass;
	var $bbclass;
	
	# Already loaded classes?
	var $classes_loaded = 0;
	
	# Update caches if not present?
	var $allow_update_caches = 0;
	var $pre_db_parse_method = 'new';
	
	# Permissions
	var $parse_smilies   	= 0;
	var $parse_html      	= 0;
	var $parse_bbcode    	= 1;
	var $strip_quotes    	= 1;
	var $parse_nl2br     	= 1;
	var $bypass_badwords 	= 0;
	var $parse_custombbcode = 1;
	var $parsing_signature  = 0;
	
	# Error
	var $error;
	
    /*-------------------------------------------------------------------------*/
    // Constructor
    /*-------------------------------------------------------------------------*/
    
    function parse_bbcode()
    {
    	//-----------------------------------------
    	// Anything to init?
    	//-----------------------------------------
    }
    
    /*-------------------------------------------------------------------------*/
    // This function is called before inserting the post text into the DB
    // Depending on which method we're using, it will either check for errors
    // or parse the code into legacy (IPB 2.0<) format.
    /*-------------------------------------------------------------------------*/
    
    function pre_db_parse( $text )
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	$class = "";
    	
    	//-----------------------------------------
    	// Get the correct class
    	//-----------------------------------------
    	
		if ( ! $this->classes_loaded )
    	{
    		$this->_load_classes();    	
		}
    	
    	//-----------------------------------------
    	// Check the DB cache
    	//-----------------------------------------
    	
    	$this->check_caches();
    	
    	//-----------------------------------------
    	// Decide the settings...
    	//-----------------------------------------
    	
    	$this->bbclass->bypass_badwords   	= $this->bypass_badwords ? $this->bypass_badwords : intval($this->ipsclass->member['g_bypass_badwords']);
    	$this->bbclass->parse_smilies     	= $this->parse_smilies;
    	$this->bbclass->parse_html        	= $this->parse_html;
    	$this->bbclass->parse_bbcode      	= $this->parse_bbcode;
    	$this->bbclass->strip_quotes      	= $this->ipsclass->vars['strip_quotes'];
    	$this->bbclass->parse_nl2br       	= $this->parse_nl2br;
    	$this->bbclass->parse_wordwrap    	= $this->ipsclass->vars['post_wordwrap'];
    	$this->bbclass->max_embed_quotes  	= $this->ipsclass->vars['max_quotes_per_post'];
    	$this->bbclass->parse_custombbcode  = $this->parse_custombbcode;
    	$this->bbclass->parsing_signature   = $this->parsing_signature;

    	//-----------------------------------------
    	// Parse
    	//-----------------------------------------
    	
    	return $this->bbclass->pre_db_parse( $text );
    }
    
    /*-------------------------------------------------------------------------*/
    // This function is called before showing the post for edit
    /*-------------------------------------------------------------------------*/
    
    function pre_edit_parse( $text )
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	$class = "";
    	
    	//-----------------------------------------
    	// Check the DB cache
    	//-----------------------------------------
    	
    	$this->check_caches();
    	
    	//-----------------------------------------
    	// Get the correct class
    	//-----------------------------------------
    	
		if ( ! $this->classes_loaded )
    	{
    		$this->_load_classes();    	
		}
    	
    	//-----------------------------------------
    	// Decide the settings...
    	//-----------------------------------------
    	
    	$this->bbclass->bypass_badwords   	= $this->bypass_badwords ? $this->bypass_badwords : intval($this->ipsclass->member['g_bypass_badwords']);
    	$this->bbclass->parse_smilies    	= $this->parse_smilies;
    	$this->bbclass->parse_html       	= $this->parse_html;
    	$this->bbclass->parse_bbcode     	= $this->parse_bbcode;
    	$this->bbclass->strip_quotes     	= $this->ipsclass->vars['strip_quotes'];
    	$this->bbclass->parse_nl2br      	= $this->parse_nl2br;
    	$this->bbclass->parse_wordwrap   	= $this->ipsclass->vars['post_wordwrap'];
    	$this->bbclass->max_embed_quotes 	= $this->ipsclass->vars['max_quotes_per_post'];
    	$this->bbclass->parse_custombbcode  = $this->parse_custombbcode;
    	$this->bbclass->parsing_signature	= $this->parsing_signature;

    	//-----------------------------------------
    	// Parse
    	//-----------------------------------------
    	
    	return $this->bbclass->pre_edit_parse( $text );
    }
    
    /*-------------------------------------------------------------------------*/
    // This function is called before displaying the final post in the user's browser
    /*-------------------------------------------------------------------------*/
    
    function pre_display_parse( $text )
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	$class = "";
    	
    	//-----------------------------------------
    	// Check the DB cache
    	//-----------------------------------------
    	
    	$this->check_caches();
    	
    	//-----------------------------------------
    	// Get the correct class
    	//-----------------------------------------
    	
		if ( ! $this->classes_loaded )
    	{
    		$this->_load_classes();    	
		}
    	
    	//-----------------------------------------
    	// Decide the settings...
    	//-----------------------------------------
    	
    	$this->bbclass->bypass_badwords   	= $this->bypass_badwords ? $this->bypass_badwords : intval($this->ipsclass->member['g_bypass_badwords']);
    	$this->bbclass->parse_smilies    	= $this->parse_smilies;
    	$this->bbclass->parse_html       	= $this->parse_html;
    	$this->bbclass->parse_bbcode     	= $this->parse_bbcode;
    	$this->bbclass->strip_quotes     	= $this->ipsclass->vars['strip_quotes'];
    	$this->bbclass->parse_nl2br      	= $this->parse_nl2br;
    	$this->bbclass->parse_wordwrap   	= $this->ipsclass->vars['post_wordwrap'];
    	$this->bbclass->max_embed_quotes 	= $this->ipsclass->vars['max_quotes_per_post'];
    	$this->bbclass->parse_custombbcode  = $this->parse_custombbcode;
    	$this->bbclass->parsing_signature	= $this->parsing_signature;

    	//-----------------------------------------
    	// Parse
    	//-----------------------------------------
    	
    	return $this->bbclass->pre_display_parse( $text );
    }
    
     /*-------------------------------------------------------------------------*/
    // Function: convert_std_to_rte
    /*-------------------------------------------------------------------------*/
    
    /**
    * Convert STD contents TO RTE compatible
    *
    * Used when switching between editors or
    * when using the fast reply and hitting "More..."
    *
    * @param	string	STD text
    * @return	string	RTE	text
    */
    function convert_std_to_rte( $t )
    {
    	//-----------------------------------------
    	// Get the correct class
    	//-----------------------------------------
    	
		if ( ! $this->classes_loaded )
    	{
    		$this->_load_classes();    	
		}
    	
    	//-----------------------------------------
		// Ensure no slashy slashy
		//-----------------------------------------
		
		$t = str_replace( '"','&quot;', $t );
		$t = str_replace( "'",'&apos;', $t );
		
		//-----------------------------------------
		// Convert <>
		//-----------------------------------------
		if( $this->parse_nl2br ) { $t = str_replace( "<br />", "\n", $t ); }
		
		$t = str_replace( '<', '&lt;', $t );
		$t = str_replace( '>', '&gt;', $t );
		
		//-----------------------------------------
    	// RTE expects <br /> not \n
    	//-----------------------------------------
    	
    	$t = str_replace( "\n", "<br />", str_replace( "\r\n", "\n", $t ) );
    	
    	//-----------------------------------------
    	// Okay, convert ready for DB
    	//-----------------------------------------
    	
    	$t = $this->pre_db_parse( $t );
    	
    	$t = $this->bbclass->clean_ipb_html( $t );
    	
    	return $t;
    }
    
    /*-------------------------------------------------------------------------*/
    // Passthrough function: Clean up IPB HTML
    /*-------------------------------------------------------------------------*/
    
    function convert_ipb_html_to_html( $t )
    {
    	//-----------------------------------------
    	// Get the correct class
    	//-----------------------------------------
    	
		if ( ! $this->classes_loaded )
    	{
    		$this->_load_classes();    	
		}
    	
    	return $this->bbclass->clean_ipb_html( $t );
    }
    
    /*-------------------------------------------------------------------------*/
    // Passthrough function: Strip all tags
    /*-------------------------------------------------------------------------*/
    
    function strip_all_tags( $t, $pre_edit_parse=1 )
    {
    	//-----------------------------------------
    	// Get the correct class
    	//-----------------------------------------
    	
		if ( ! $this->classes_loaded )
    	{
    		$this->_load_classes();    	
		}
    	
    	return $this->bbclass->strip_all_tags( $t, $pre_edit_parse );
    }
    
    /*-------------------------------------------------------------------------*/
    // Passthrough function: Badwords
    /*-------------------------------------------------------------------------*/
    
    function bad_words( $t )
    {
    	//-----------------------------------------
    	// Get the correct class
    	//-----------------------------------------
    	
		if ( ! $this->classes_loaded )
    	{
    		$this->_load_classes();    	
		}
		
		$this->check_caches();
    	
    	$this->bbclass->bypass_badwords   = isset( $this->bypass_badwords ) ? $this->bypass_badwords : intval($this->ipsclass->member['g_bypass_badwords']);
    	
    	return $this->bbclass->bad_words( $t );
    }
    
    /*-------------------------------------------------------------------------*/
    // Passthrough function: Parse poll tags
    /*-------------------------------------------------------------------------*/
    
    function parse_poll_tags( $txt )
    {
    	//-----------------------------------------
    	// Get the correct class
    	//-----------------------------------------
    	
		if ( ! $this->classes_loaded )
    	{
    		$this->_load_classes();    	
		}
    	
    	//-----------------------------------------
		// [url]http://www.index.com[/url]
		// [url=http://www.index.com]ibforums![/url]
		//-----------------------------------------
		
		$txt = preg_replace_callback( "#\[url\](.*?)\[/url\]#is"                                                    , array( &$this->bbclass, '_regex_build_url_tags'), $txt );
		$txt = preg_replace_callback( "#\[url\s*=\s*(?:\&quot\;|\")\s*(.*?)\s*(?:\&quot\;|\")\s*\](.*?)\[\/url\]#is", array( &$this->bbclass, '_regex_build_url_tags'), $txt );
		$txt = preg_replace_callback( "#\[url\s*=\s*(.*?)\s*\](.*?)\[\/url\]#is"                                    , array( &$this->bbclass, '_regex_build_url_tags'), $txt );
			
		if ( $this->ipsclass->vars['allow_images'] )
		{
			$txt = preg_replace_callback( "#\[img\](.+?)\[/img\]#i", array( &$this->bbclass, 'regex_check_image' ), $txt );
		}
    	
    	return $txt;
    }
    
    /*-------------------------------------------------------------------------*/
    // Passthrough function: make_quote_safe
    /*-------------------------------------------------------------------------*/
    
    function make_quote_safe( $t )
    {
    	//-----------------------------------------
    	// Get the correct class
    	//-----------------------------------------
    	
		if ( ! $this->classes_loaded )
    	{
    		$this->_load_classes();    	
		}
    	
    	return $this->bbclass->_make_quote_safe( $t );
    }
    
    /*-------------------------------------------------------------------------*/
    // Passthrough function: Strip Quotes
    /*-------------------------------------------------------------------------*/
    
    function strip_quotes( $t )
    {
    	//-----------------------------------------
    	// Get the correct class
    	//-----------------------------------------
    	
		if ( ! $this->classes_loaded )
    	{
    		$this->_load_classes();    	
		}
    	
    	return $this->bbclass->strip_quotes( $t );
    }
    
    /*-------------------------------------------------------------------------*/
	// CHECK (AND LOAD) CACHES
	// This function loads all the data we'll need.
	/*-------------------------------------------------------------------------*/
	
	function check_caches()
	{
		//-----------------------------------------
		// Check emoticons
		//-----------------------------------------
		
		if ( !isset($this->ipsclass->cache['emoticons']) OR !is_array( $this->ipsclass->cache['emoticons'] ) )
		{
			$this->ipsclass->cache['emoticons'] = array();
			
			$this->ipsclass->DB->simple_construct( array( 'select' => 'typed,image,clickable,emo_set', 'from' => 'emoticons' ) );
			$this->ipsclass->DB->simple_exec();
		
			while ( $r = $this->ipsclass->DB->fetch_row() )
			{
				$this->ipsclass->cache['emoticons'][] = $r;
			}
			
			@usort( $this->ipsclass->cache['emoticons'] , array( &$this->bbclass, 'smilie_length_sort' ) );

			if ( $this->allow_update_caches )
			{
				$this->ipsclass->update_cache( array( 'name' => 'emoticons', 'array' => 1, 'deletefirst' => 1 ) );
			}
		}
		
		//-----------------------------------------
		// Check BBCode
		//-----------------------------------------
		
		if ( !isset($this->ipsclass->cache['bbcode']) OR !is_array( $this->ipsclass->cache['bbcode'] ) )
		{
			$this->ipsclass->cache['bbcode'] = array();
			
			$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'custom_bbcode' ) );
			$bbcode = $this->ipsclass->DB->simple_exec();
		
			while ( $r = $this->ipsclass->DB->fetch_row($bbcode) )
			{
				$this->ipsclass->cache['bbcode'][] = $r;
			}
			
			if ( $this->allow_update_caches )
			{
				$this->ipsclass->update_cache( array( 'name' => 'bbcode', 'array' => 1, 'deletefirst' => 1 ) );
			}
		}
		
		//-----------------------------------------
		// Check badwords
		//-----------------------------------------
		
		if ( !isset($this->ipsclass->cache['badwords']) OR !is_array( $this->ipsclass->cache['badwords'] ) )
		{
			$this->ipsclass->cache['badwords'] = array();
			
			$this->ipsclass->DB->simple_construct( array( 'select' => 'type,swop,m_exact', 'from' => 'badwords' ) );
			$bbcode = $this->ipsclass->DB->simple_exec();
		
			while ( $r = $this->ipsclass->DB->fetch_row($bbcode) )
			{
				$this->ipsclass->cache['badwords'][] = $r;
			}
			
			if( count($this->ipsclass->cache['badwords']) )
			{
				@usort( $this->ipsclass->cache['badwords'] , array( &$this->bbclass, 'word_length_sort' ) );
			}
			
			if ( $this->allow_update_caches )
			{
				$this->ipsclass->update_cache( array( 'name' => 'badwords', 'array' => 1, 'deletefirst' => 1 ) );
			}
		}
	}

	/*-------------------------------------------------------------------------*/
	// Load classes
	/*-------------------------------------------------------------------------*/
	 
	function _load_classes()
	{
		if ( ! $this->classes_loaded )
    	{
			require_once( ROOT_PATH . 'sources/classes/bbcode/class_bbcode_core.php' );
			
			switch( $this->pre_db_parse_method )
			{
				case 'legacy':
					$class = 'class_bbcode_legacy.php';
					break;
				default:
					$class = 'class_bbcode.php';
					break;
			}
			
			require_once ( ROOT_PATH . 'sources/classes/bbcode/' . $class );
			
			$this->bbclass           = new class_bbcode();
			$this->bbclass->ipsclass =& $this->ipsclass;
			
			$this->classes_loaded    = 1;
			$this->error             =& $this->bbclass->error;
    	}
	}
    
        
}

?>