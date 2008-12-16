<?php

/*
+---------------------------------------------------------------------------
|   Invision Power Board
|   ========================================
|   by Matthew Mecham
|   (c) 2004 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
+---------------------------------------------------------------------------
|   INVISION POWER BOARD IS NOT FREE SOFTWARE!
|   http://www.invisionboard.com
+---------------------------------------------------------------------------
|
|   > RSS FUNCTIONS (Simple, light parser)
|   > Script written by Matt Mecham
|   > Date started: Monday 14th February 2005 (10:19) Happy Valentines Day!
|
+---------------------------------------------------------------------------
*/

/**
* RSS class for creation and extraction of RSS feeds.
*
* EXAMPLE: (CREATING AN RSS FEED)
* <code>
* $rss = new class_rss();
* 
* $channel_id = $rss->create_add_channel( array( 'title'       => 'My RSS Feed',
* 											  	 'link'        => 'http://www.mydomain.com/rss/',
* 											     'description' => 'The latest news from my <blog>',
* 											     'pubDate'     => $rss->rss_unix_to_rfc( time() ),
* 											     'webMaster'   => 'me@mydomain.com (Matt Mecham)' ) );
* 											   
* $rss->create_add_item( $channel_id, array( 'title'       => 'Hello World!',
* 										     'link'        => 'http://www.mydomain.com/blog/helloworld.html',
* 										     'description' => 'The first ever post!',
* 										     'content'     => 'Hello world! This is the blog content',
* 										     'pubDate'	   => $rss->rss_unix_to_rfc( time() ) ) );
* 										   
* $rss->create_add_item( $channel_id, array( 'title'       => 'Second Blog!!',
* 										     'link'        => 'http://www.mydomain.com/blog/secondblog.html',
* 										     'description' => 'The second ever post!',
* 										     'content'     => 'More content',
* 										     'pubDate'	   => $rss->rss_unix_to_rfc( time() ) ) );
* 										   
* $rss->create_add_image( $channel_id, array( 'title'     => 'My Image',
* 											   'url'       => 'http://mydomain.com/blog/image.gif',
* 											   'width'     => '110',
* 											   'height'    => '400',
* 											   'description' => 'Image title text' ) );
* 											 
* $rss->rss_create_document();
* 
* print $rss->rss_document;
* </code>
* EXAMPLE: (READ AN RSS FEED)
* <code>
* $rss = new class_rss();
*
* $rss->rss_parse_feed_from_url( 'http://www.mydomain.com/blog/rss/' );
*
* foreach( $rss->rss_channels as $channel_id => $channel_data )
* {
* 	print "Title: ".$channel_data['title']."<br />";
* 	print "Description; ".$channel_data['description']."<br />";
* 	
* 	foreach( $rss->rss_items[ $channel_id ] as $item_id => $item_data )
* 	{
* 		print "Item title: ".$item_data['title']."<br />";
* 		print "Item URL: ".$item_data['link']."<br />";
* 		print $item_data['content']."<hr>";
* 	}
* 	
* 	print $rss->rss_format_image( $rss->rss_images[ $channel_id ] );
* }
* </code>
*
* @package		IPS_KERNEL
* @author		Matt Mecham
* @copyright	Invision Power Services, Inc.
* @version		2.1
*/

if ( ! defined( 'IPS_CLASSES_PATH' ) )
{
	/**
	* Define classes path
	*/
	define( 'IPS_CLASSES_PATH', dirname(__FILE__) );
}

/**
* RSS class for creation and extraction of RSS feeds.
*
* Does what it says on the tin.
*
* @package	IPS_KERNEL
* @author   Matt Mecham
* @version	2.1
*/
class class_rss
{
	/**
	* IPSCLASS object
	*
	* @var object
	*/
	var $ipsclass;
	
	/**
	* Class file management object
	*
	* @var object
	*/
	var $class_file_management;
	
	/**
	* DOC type
	*
	* @ var string
	*/
	var $doc_type = 'UTF-8';
	var $orig_doc_type = "";
	
	/**
	* Error capture
	*
	* @var array
	*/
	var $errors = array();
	
	/**
	* Use sockets flag
	*
	* @var integer boolean
	*/
	var $use_sockets   = 0;
	
	/**#@+
	* Work item
	*
	* @var integer 
	*/
	var $in_item       = 0;
	var $in_image	   = 0;
	var $in_channel    = 0;
	var $rss_count     = 0;
	var $rss_max_show  = 3;
	var $cur_item      = 0;
	var $cur_channel   = 0;
	var $set_ttl        = 60;
	/**#@-*/
	
	/**
	* Dunno... should find out
	*
	* @var string
	*/
	var $tag           = "";
	
	/**#@+
	* RSS Items
	*
	* @var array 
	*/
	var $rss_items     = array();
	var $rss_headers   = array();
	var $rss_images    = array();
	var $rss_tag_names = array();
	/**#@-*/
	
	/**#@+
	* RSS Parse Items
	*
	* @var string 
	*/
	var $rss_title;
	var $rss_description;
	var $rss_link;
	var $rss_date;
	var $rss_creator;
	var $rss_content;
	var $rss_category;
	var $rss_guid;
	/**#@-*/
	
	/**#@+
	* RSS Parse Images
	*
	* @var string 
	*/
	var $rss_img_url;
	var $rss_img_title;
	var $rss_img_link;
	var $rss_img_width;
	var $rss_img_height;
	var $rss_img_desc;
	/**#@-*/
	
	/**#@+
	* RSS Channel items
	*
	* @var string 
	*/
	var $rss_chan_title;
	var $rss_chan_link;
	var $rss_chan_desc;
	var $rss_chan_date;
	var $rss_chan_lang;
	var $rss_document;
	/**#@-*/
	
	/**#@+
	* Create: Channels
	*
	* @var array 
	*/
	var $channels       = array();
	var $items          = array();
	var $channel_images = array();
	/**#@-*/
	
	/**#@+
	* Set Authentication
	*
	* @var strings 
	*/
	var $auth_req       = 0;
	var $auth_user;
	var $auth_pass;
	/**#@-*/
	
	/**
	* Convert char set
	*
	* @var	int	Boolean
	*/
	var $convert_charset = 0;
	
	var $collapse_newlines = 0;
	
	/**
	* Destination charset
	*
	* @var	int	Boolean
	*/
	var $destination_charset = 'UTF-8';
	
	/**
	* Feed char set
	*
	* @var	int	Boolean
	*/
	var $feed_charset = 'UTF-8';
	
	
	/*-------------------------------------------------------------------------*/
	// Constructor
	/*-------------------------------------------------------------------------*/

	function class_rss()
	{
		$this->rss_tag_names = array( 'ITEM'            => 'ITEM',
									  'IMAGE'           => 'IMAGE',
									  'URL'             => 'URL',
									  'CONTENT:ENCODED' => 'CONTENT:ENCODED',
									  'CONTENT'			=> 'CONTENT',
									  'DESCRIPTION'     => 'DESCRIPTION',
									  'TITLE'			=> 'TITLE',
									  'LINK'		    => 'LINK',
									  'CREATOR'         => 'CREATOR',
									  'PUBDATE'		    => 'DATE',
									  'DATE'		    => 'DATE',
									  'DC:CREATOR'      => 'CREATOR',
									  'DC:DATE'	        => 'DATE',
									  'DC:LANGUAGE'     => 'LANGUAGE',
									  'WEBMASTER'       => 'WEBMASTER',
									  'LANGUAGE'        => 'LANGUAGE',
									  'CHANNEL'         => 'CHANNEL',
									  'CATEGORY'	    => 'CATEGORY',
									  'GUID'			=> 'GUID',
									  'WIDTH'			=> 'WIDTH',
									  'HEIGHT'			=> 'HEIGHT',
									);
	}
	
	/*-------------------------------------------------------------------------*/
	// Create RSS 2.0 document
	/*-------------------------------------------------------------------------*/
	
	/**
	* Create the RSS document
	*
	* @return	void
	*/
	function rss_create_document( )
	{
		if ( ! count( $this->channels ) )
		{
			$this->errors[] = "No channels defined";
		}
		
		$this->rss_document  = '<?xml version="1.0" encoding="'.$this->doc_type.'" ?'.'>'."\n";
		$this->rss_document .= '<rss version="2.0">'."\n";
		
		//-------------------------------
		// Add channels
		//-------------------------------
		
		foreach( $this->channels as $idx => $channel )
		{
			$tmp_data = "";
			$had_ttl  = 0;
			
			//-------------------------------
			// Add channel data
			//-------------------------------
			
			foreach( $channel as $tag => $data )
			{
				if ( strtolower($tag) == 'ttl' )
				{
					$had_ttl = 1;
				}
				
				$tmp_data .= "\t<" . $tag . ">" . $this->_xml_encode_string($data) . "</" . $tag . ">\n";
			}
			
			//-------------------------------
			// Added TTL?
			//-------------------------------
			
			if ( ! $had_ttl )
			{
				$tmp_data .= "\t<ttl>" . intval($this->set_ttl) . "</ttl>\n";
			}
			
			//-------------------------------
			// Got image?
			//-------------------------------
			
			if ( isset($this->channel_images[ $idx ]) AND is_array( $this->channel_images[ $idx ] ) AND count( $this->channel_images[ $idx ] ) )
			{
				foreach( $this->channel_images[ $idx ] as $image )
				{
					$tmp_data .= "\t<image>\n";
					
					foreach( $image as $tag => $data )
					{
						$tmp_data .= "\t\t<" . $tag . ">" . $this->_xml_encode_string($data) . "</" . $tag . ">\n";
					}
					
					$tmp_data .= "\t</image>\n";
				}
			}
			
			//-------------------------------
			// Add item data
			//-------------------------------
			
			if ( is_array( $this->items[ $idx ] ) and count( $this->items[ $idx ] ) )
			{
				foreach( $this->items[ $idx ] as $item )
				{
					$tmp_data .= "\t<item>\n";
					
					foreach( $item as $tag => $data )
					{
						$extra = "";
						
						if ( $tag == 'guid' AND ! strstr( $data, 'http://' ) )
						{
							$extra = ' isPermaLink="false"';
						}
						
						$tmp_data .= "\t\t<" . $tag . $extra . ">" . $this->_xml_encode_string($data) . "</" . $tag . ">\n";
					}
					
					$tmp_data .= "\t</item>\n";
				}
			}
			
			//-------------------------------
			// Put it together...
			//-------------------------------
			
			$this->rss_document .= "<channel>\n";
			$this->rss_document .= $tmp_data;
			$this->rss_document .= "</channel>\n";
		}
		
		$this->rss_document .= "</rss>";
		
		//-------------------------------
		// Clean up
		//-------------------------------
		
		$this->channels       = array();
		$this->items          = array();
		$this->channel_images = array();
	}
	
	/*-------------------------------------------------------------------------*/
	// Create RSS 2.0 document: Add channel
	/*-------------------------------------------------------------------------*/
	
	/**
	* Create RSS 2.0 document: Add channel
	*
	* title, link, description,language,pubDate,lastBuildDate,docs,generator
	* managingEditor,webMaster
	*
	* @return	integer	New channel ID
	*/
	function create_add_channel( $in=array() )
	{
		$this->channels[ $this->cur_channel ] = $in;
		
		//-------------------------------
		// Inc. and return
		//-------------------------------
		
		$return = $this->cur_channel;
		
		$this->cur_channel++;
		
		return $return;
	}
	
	/*-------------------------------------------------------------------------*/
	// Create RSS 2.0 document: Add channel image item
	/*-------------------------------------------------------------------------*/
	
	/**
	* Create RSS 2.0 document: Add channel image item
	*
	* url, link, title, width, height, description
	*
	* @param	integer Channel ID
	* @param	array	Array of image variables
	* @return	void
	*/
	function create_add_image( $channel_id=0, $in=array() )
	{
		$this->channel_images[ $channel_id ][] = $in;
	}
	
	/*-------------------------------------------------------------------------*/
	// Create RSS 2.0 document: Add item
	// title,description,pubDate,guid,content,category,link
	/*-------------------------------------------------------------------------*/
	
	/**
	* Create RSS 2.0 document: Add item
	*
	* title,description,pubDate,guid,content,category,link
	*
	* @param	integer Channel ID
	* @param	array	Array of item variables
	* @return	void
	*/
	function create_add_item( $channel_id=0, $in=array() )
	{
		$this->items[ $channel_id ][] = $in;
	}
	
	/*-------------------------------------------------------------------------*/
	// Tool: Format an img
	/*-------------------------------------------------------------------------*/
	
	/**
	* Create RSS 2.0 document: Format Image
	*
	*
	* @param	array	Array of item variables
	* @return	string	Image HTML
	*/
	function rss_format_image( $in=array() )
	{
		if ( ! $in['url'] )
		{
			$this->errors[] = "Cannot format image, not enough input";
		}
		
		$title  = "";
		$alt    = "";
		$width  = "";
		$height = "";
		
		if ( $in['description'] )
		{
			$title = " title='".$this->_xml_encode_attribute( $in['description'] )."' ";
		}
		
		if ( $in['title'] )
		{
			$alt = " alt='".$this->_xml_encode_attribute( $in['title'] )."' ";
		}
		
		if ( $in['width'] )
		{
			if ( $in['width'] > 144 )
			{
				$in['width'] = 144;
			}
			
			$width = " width='".$this->_xml_encode_attribute( $in['width'] )."' ";
		}
		
		if ( $in['height'] )
		{
			if ( $in['height'] > 400 )
			{
				$in['height'] = 400;
			}
			
			$height = " height='".$this->_xml_encode_attribute( $in['height'] )."' ";
		}
		
		//-------------------------------
		// Draw image
		//-------------------------------
		
		$img = "<img src='".$in['url']."' $title $alt $width $height />";
		
		//-------------------------------
		// Linked?
		//-------------------------------
		
		if ( $in['link'] )
		{
			$img = "<a href='".$in['link']."'>".$img."</a>";
		}
		
		return $img;
	}
	
	/*-------------------------------------------------------------------------*/
	// Tool: Format unixdate to rfc date
	/*-------------------------------------------------------------------------*/
	
	/**
	* Create RSS 2.0 document: Format unixdate to rfc date
	*
	*
	* @param	integer	Unix timestamp
	* @return	string	Formatted date RFC date
	*/
	function rss_unix_to_rfc( $time )
	{
		return date( 'r', $time );
	}
	
	/*-------------------------------------------------------------------------*/
	// Parse Feed (FROM URL)
	/*-------------------------------------------------------------------------*/
	
	/**
	* Extract: Parse RSS document from URL
	*
	*
	* @param	string	URI
	* @return	void
	*/
	function rss_parse_feed_from_url( $feed_location )
	{
		//-----------------------------------------
		// Load file management class
		//-----------------------------------------
		
		require_once( IPS_CLASSES_PATH.'/class_file_management.php' );
		$this->class_file_management = new class_file_management();
		
		$this->class_file_management->use_sockets = $this->use_sockets;
		
		$this->class_file_management->auth_req  = $this->auth_req;
		$this->class_file_management->auth_user = $this->auth_user;
		$this->class_file_management->auth_pass = $this->auth_pass;
		
		//-------------------------------
		// Reset arrays
		//-------------------------------
		
		$this->rss_items    = array();
		$this->rss_channels = array();
		
		//-------------------------------
		// Get data
		//-------------------------------
		
		$data = $this->class_file_management->get_file_contents( $feed_location );
		
		if ( count( $this->class_file_management->errors ) )
		{
			$this->errors = $this->class_file_management->errors;
			@xml_parser_free($xml_parser); // Let's kill the parser before we return
			return FALSE;
		}
		
		if( preg_match( "#encoding=[\"'](\S+?)[\"']#si", $data, $matches ) )
		{
			$this->orig_doc_type = $matches[1];
		}
		
		if( preg_match( "#charset=(\S+?)#si", $data, $matches ) )
		{
			$this->orig_doc_type = $matches[1];
		}
		
		
		//-----------------------------------------
		// Charset conversion?
		//-----------------------------------------
		
		$supported_encodings = array( "utf-8", "iso-8859-1", "us-ascii" );
		$this->doc_type = in_array( strtolower($this->doc_type), $supported_encodings ) ? $this->doc_type : "utf-8";
	
		if ( $this->convert_charset AND $data )
		{
			if ( $this->feed_charset != $this->doc_type )
			{
				$data = $this->ipsclass->txt_convert_charsets( $data, $this->feed_charset, $this->doc_type );
				
				# Replace any char-set= data
				$data = preg_replace( "#encoding=[\"'](\S+?)[\"']#si", "encoding=\"".$this->doc_type."\"", $data );
				$data = preg_replace( "#charset=(\S+?)#si"           , "charset=".$this->doc_type        , $data );
			}
		}
	
		//-------------------------------
		// Generate XML parser
		//-------------------------------

		$xml_parser = xml_parser_create( $this->doc_type );
		xml_set_element_handler(       $xml_parser, array( &$this, "parse_startElement" ), array( &$this, "parse_endElement") );
		xml_set_character_data_handler($xml_parser, array( &$this, "parse_characterData" ) );
		
		//-------------------------------
		// Parse data
		//-------------------------------
		
		if ( ! xml_parse( $xml_parser, $data ) )
		{
			$this->errors[] = sprintf("XML error: %s at line %d",  xml_error_string( xml_get_error_code($xml_parser) ), xml_get_current_line_number($xml_parser) );
		}
		
		//-------------------------------
		// Free memory used by XML parser
		//-------------------------------
		
		@xml_parser_free($xml_parser);
	}
	
	/*-------------------------------------------------------------------------*/
	// Parse Feed (FROM FILE)
	/*-------------------------------------------------------------------------*/
	
	/**
	* Extract: Parse RSS document from file
	*
	*
	* @param	string	Path
	* @return	void
	*/
	function rss_parse_feed_from_file( $feed_location )
	{
		//-------------------------------
		// Alias...
		//-------------------------------
		
		$this->rss_parse_feed_from_url( $feed_location );
	}
	
	/*-------------------------------------------------------------------------*/
	// Parse Feed (FROM DATA)
	/*-------------------------------------------------------------------------*/
	
	/**
	* Extract: Parse RSS document from data
	*
	*
	* @param	string	Raw RSS data
	* @return	void
	*/
	function rss_parse_feed_from_data( $data )
	{
		//-------------------------------
		// Reset arrays
		//-------------------------------
		
		$this->rss_items    = array();
		$this->rss_channels = array();
		$this->cur_channel  = 0;
		
		//-------------------------------
		// Generate XML parser
		//-------------------------------
		
		$xml_parser = xml_parser_create( $this->doc_type );
		xml_set_element_handler(       $xml_parser, array( &$this, "parse_startElement" ), array( &$this, "parse_endElement") );
		xml_set_character_data_handler($xml_parser, array( &$this, "parse_characterData" ) );
		
		
		if ( ! xml_parse( $xml_parser, $data, TRUE ) )
		{
			$this->errors[] = sprintf("XML error: %s at line %d",  xml_error_string( xml_get_error_code($xml_parser) ), xml_get_current_line_number($xml_parser) );
		}
		
		//-------------------------------
		// Free memory used by XML parser
		//-------------------------------
		
		xml_parser_free($xml_parser);
	}
	
	/*-------------------------------------------------------------------------*/
	// START ELEMENT
	/*-------------------------------------------------------------------------*/
	
	/**
	* Extract: Call back function for element handler
	*
	*
	* @param	string	Parser object
	* @param	string	Tag name
	* @param	array	Attributes
	* @return	void
	*/
	function parse_startElement($parser, $name, $attrs)
	{
		//-------------------------------
		// Just in case
		//-------------------------------
		
		$name = strtoupper($name);
		
		if ( $this->in_item )
		{
			$this->in_item++;
			$this->tag = $this->rss_tag_names[ $name ];
		}
		
		if ( $this->in_image )
		{
			$this->in_image++;
			$this->tag = $this->rss_tag_names[ $name ];
		}
		
		if ( $this->in_channel )
		{
			$this->in_channel++;
			$this->tag = isset($this->rss_tag_names[ $name ]) ? $this->rss_tag_names[ $name ] : '';
		}
		
		if ( isset($this->rss_tag_names[ $name ]) AND $this->rss_tag_names[ $name ] == "ITEM" )
		{
			$this->in_item = 1;
		} 
		else if ( isset($this->rss_tag_names[ $name ]) AND $this->rss_tag_names[ $name ] == "IMAGE")
		{
			$this->in_image = 1;
		}
		else if ( isset($this->rss_tag_names[ $name ]) AND $this->rss_tag_names[ $name ] == "CHANNEL")
		{
			$this->in_channel = 1;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// END ELEMENT
	/*-------------------------------------------------------------------------*/
	
	/**
	* Extract: Call back function for element handler
	*
	*
	* @param	string	Parser object
	* @param	string	Tag name
	* @return	void
	*/
	function parse_endElement($parser, $name)
	{
		//-------------------------------
		// Just in case
		//-------------------------------
		
		$name = strtoupper($name);
		
		if ( isset($this->rss_tag_names[ $name ]) AND $this->rss_tag_names[ $name ] == "IMAGE" )
		{
			$this->rss_images[ $this->cur_channel ]['url']         = $this->rss_img_image;
			$this->rss_images[ $this->cur_channel ]['title']       = $this->rss_img_title;
			$this->rss_images[ $this->cur_channel ]['link']        = $this->rss_img_link;
			$this->rss_images[ $this->cur_channel ]['width']       = $this->rss_img_width;
			$this->rss_images[ $this->cur_channel ]['height']      = $this->rss_img_height;
			$this->rss_images[ $this->cur_channel ]['description'] = $this->rss_img_desc;
			
			$this->_kill_image_elements();
			$this->in_image = 0;
		}
		else if ( isset($this->rss_tag_names[ $name ]) AND $this->rss_tag_names[ $name ] == "CHANNEL" )
		{
			//-------------------------------
			// Add data
			//-------------------------------
			
			$this->rss_channels[ $this->cur_channel ]['title']       = $this->_format_string($this->rss_chan_title);
			$this->rss_channels[ $this->cur_channel ]['link']        = $this->_format_string($this->rss_chan_link);
			$this->rss_channels[ $this->cur_channel ]['description'] = $this->_format_string($this->rss_chan_desc);
			$this->rss_channels[ $this->cur_channel ]['date']        = $this->_format_string($this->rss_chan_date);
			$this->rss_channels[ $this->cur_channel ]['unixdate']    = @strtotime($this->_format_string($this->rss_chan_date));
			$this->rss_channels[ $this->cur_channel ]['language']    = $this->_format_string($this->rss_chan_lang);
			
			//-------------------------------
			// Increment item
			//-------------------------------
			
			$this->cur_channel++;
			
 			//-------------------------------
			// Clean up
			//-------------------------------
			
			$this->_kill_channel_elements();
			$this->in_channel = 0;
		}
		else if ( isset($this->rss_tag_names[ $name ]) AND $this->rss_tag_names[ $name ] == "ITEM" )
		{
			if ( $this->rss_count < $this->rss_max_show )
			{
				$this->rss_count++;
				
				//-------------------------------
				// Kludge for RDF which closes
				// channel before first item
				// I'm staring at you Typepad
				//-------------------------------
				
				if ( $this->cur_channel > 0 AND ( ! is_array($this->rss_items[ $this->cur_channel ] ) ) )
				{
					$this->cur_channel--;
				}
				
				$this->rss_items[ $this->cur_channel ][ $this->cur_item ]['title']       = $this->rss_title;
				$this->rss_items[ $this->cur_channel ][ $this->cur_item ]['link']        = $this->rss_link;
				$this->rss_items[ $this->cur_channel ][ $this->cur_item ]['description'] = $this->rss_description;
				$this->rss_items[ $this->cur_channel ][ $this->cur_item ]['content']     = $this->rss_content;
				$this->rss_items[ $this->cur_channel ][ $this->cur_item ]['creator']     = $this->rss_creator;
				$this->rss_items[ $this->cur_channel ][ $this->cur_item ]['date']        = $this->rss_date;
				$this->rss_items[ $this->cur_channel ][ $this->cur_item ]['unixdate']    = trim($this->rss_date) != "" ? strtotime($this->rss_date) : time();
				$this->rss_items[ $this->cur_channel ][ $this->cur_item ]['category']    = $this->rss_category;
				$this->rss_items[ $this->cur_channel ][ $this->cur_item ]['guid']        = $this->rss_guid;
				
				//-------------------------------
				// Increment item
				//-------------------------------
				
				$this->cur_item++;
				
				//-------------------------------
				// Clean up
				//-------------------------------
				
				$this->_kill_elements();
				$this->in_item = 0;
			}
			else if ($this->rss_count >= $this->rss_max_show)
			{
				//-------------------------------
				// Clean up
				//-------------------------------
				
				$this->_kill_elements();
				$this->in_item = 0;
			}
			
		}
		if ( $this->in_channel )
		{
			$this->in_channel--;
		}
		
		if ( $this->in_item )
		{
			$this->in_item--;
		}
		
		if ( $this->in_image )
		{
			$this->in_image--;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Character data
	/*-------------------------------------------------------------------------*/
	
	/**
	* Extract: Call back function for element handler
	*
	*
	* @param	string	Parser object
	* @param	string	CDATA
	* @return	void
	*/
	function parse_characterData($parser, $data)
	{
		if ( $this->in_image == 2 )
		{
			switch ($this->tag)
			{
				case "URL":
					$this->rss_img_image .= $data;
					break;
				case "TITLE":
					$this->rss_img_title .= $data;
					break;
				case "LINK":
					$this->rss_img_link .= $data;
					break;
				case "WIDTH":
					$this->rss_img_width .= $data;
					break;
				case "HEIGHT":
					$this->rss_img_height .= $data;
					break;
				case "DESCRIPTION":
					$this->rss_img_desc .= $data;
					break;
			}
		}
		
		if ( $this->in_item == 2)
		{
			switch ($this->tag)
			{
				case "TITLE":
					$this->rss_title .= $data;
					break;
				case "DESCRIPTION":
					$this->rss_description .= $data;
					break;
				case "LINK":
					if ( ! is_string($this->rss_link) )
					{
						$this->rss_link = "";
					}
					$this->rss_link .= $data;
					break;
				case "CONTENT:ENCODED":
					$this->rss_content .= $data;
					break;
				case "CONTENT":
					$this->rss_content .= $data;
					break;
				case "DATE":
					$this->rss_date .= $data;
					break;
				case "DC:DATE":
					$this->rss_date .= $data;
					break;
				case "CREATOR":
					$this->rss_creator .= $data;
					break;
				case "CATEGORY":
					$this->rss_category .= $data;
					break;
				case "GUID":
					$this->rss_guid .= $data;
					break;
			}
		}
		
		if ( $this->in_channel == 2)
		{
			switch ($this->tag)
			{
				case "TITLE":
					$this->rss_chan_title .= $data;
					break;
				case "DESCRIPTION":
					$this->rss_chan_desc .= $data;
					break;
				case "LINK":
					if ( ! is_string($this->rss_chan_link) )
					{
						$this->rss_chan_link="";
					}
					$this->rss_chan_link .= $data;
					break;
				case "DATE":
					$this->rss_chan_date .= $data;
					break;
				case "LANGUAGE":
					$this->rss_chan_lang .= $data;
					break;
			}
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// INTERNAL: Encodes an attribute string
	/*-------------------------------------------------------------------------*/
	
	/**
	* Internal: Encode attribute
	*
	*
	* @param	string	Raw Text
	* @return	string	Parsed Text
	*/
	function _xml_encode_attribute( $t )
	{
		$t = preg_replace("/&(?!#[0-9]+;)/s", '&amp;', $t );
		$t = str_replace( "<", "&lt;"  , $t );
		$t = str_replace( ">", "&gt;"  , $t );
		$t = str_replace( '"', "&quot;", $t );
		$t = str_replace( "'", '&#039;', $t );
		
		return $t;
	}
	
	/*-------------------------------------------------------------------------*/
	// INTERNAL: Encodes an attribute string
	/*-------------------------------------------------------------------------*/
	
	/**
	* Internal: Dencode attribute
	*
	*
	* @param	string	Raw Text
	* @return	string	Parsed Text
	*/
	function _xml_decode_attribute( $t )
	{
		$t = str_replace( "&amp;" , "&", $t );
		$t = str_replace( "&lt;"  , "<", $t );
		$t = str_replace( "&gt;"  , ">", $t );
		$t = str_replace( "&quot;", '"', $t );
		$t = str_replace( "&#039;", "'", $t );
		
		return $t;
	}
	
	/*-------------------------------------------------------------------------*/
	// INTERNAL: Encodes a string to make it safe (uses cdata)
	/*-------------------------------------------------------------------------*/
	
	/**
	* Internal: Encode string
	*
	*
	* @param	string	Raw Text
	* @return	string	Parsed Text
	*/
	function _xml_encode_string( $v )
	{
		# Fix up encoded & " ' and any other funnky IPB data
		$v = str_replace( '&amp;'         , '&'          , $v );
		$v = str_replace( "&#60;&#33;--"  , "&lt!--"     , $v );
		$v = str_replace( "--&#62;"		  , "--&gt;"     , $v );
		$v = str_replace( "&#60;script"   , "&lt;script" , $v );
		$v = str_replace( "&quot;"        , "\""         , $v );
		$v = str_replace( "&#036;"        , '$'          , $v );
		$v = str_replace( "&#33;"         , "!"          , $v );
		$v = str_replace( "&#39;"         , "'"          , $v );
		
		if ( preg_match( "/['\"\[\]<>&]/", $v ) )
		{
			$v = "<![CDATA[" . $this->_xml_convert_safecdata($v) . "]]>";
		}
		
		if ( $this->collapse_newlines )
		{
			$v = str_replace( "\r\n", "\n", $v );
		}
		
		return $v;
	}
	
	/*-------------------------------------------------------------------------*/
	// INTERNAL: Ensures no embedding of cdata
	/*-------------------------------------------------------------------------*/
	
	/**
	* Encode CDATA XML attribute (Make safe for transport)
	*
	* @param	string	Raw data
	* @return	string	Converted Data
	*/
	
	function _xml_convert_safecdata( $v )
	{
		# Legacy
		//$v = str_replace( "<![CDATA[", "<!¢|CDATA|", $v );
		//$v = str_replace( "]]>"      , "|¢]>"      , $v );
		
		# New
		$v = str_replace( "<![CDATA[", "<!#^#|CDATA|", $v );
		$v = str_replace( "]]>"      , "|#^#]>"      , $v );
		
		return $v;
	}
	
	/*-------------------------------------------------------------------------*/
	// INTERNAL: Uncoverts safe embedding
	/*-------------------------------------------------------------------------*/
	
	/**
	* Decode CDATA XML attribute (Make safe for transport)
	*
	* @param	string	Raw data
	* @return	string	Converted Data
	*/
	
	function _xml_unconvert_safecdata( $v )
	{
		# Legacy
		$v = str_replace( "<!¢|CDATA|", "<![CDATA[", $v );
		$v = str_replace( "|¢]>"      , "]]>"      , $v );
		
		# New
		$v = str_replace( "<!#^#|CDATA|", "<![CDATA[", $v );
		$v = str_replace( "|#^#]>"      , "]]>"      , $v );
		
		return $v;
	}
	
	/*-------------------------------------------------------------------------*/
	// Format text string
	/*-------------------------------------------------------------------------*/
	
	function _format_string( $t )
	{
		return trim( $t );
	}
	
	/*-------------------------------------------------------------------------*/
	// Kill work elements
	/*-------------------------------------------------------------------------*/
	
	/**
	* Internal: Reset arrays
	*
	* @return	void
	*/
	function _kill_elements()
	{
		$this->rss_link        = "";
		$this->rss_title       = "";
		$this->rss_description = "";
		$this->rss_content     = "";
		$this->rss_date        = "";
		$this->rss_creator     = "";
		$this->rss_category    = "";
		$this->rss_guid        = "";
	}
	
	/*-------------------------------------------------------------------------*/
	// Kill img elements
	/*-------------------------------------------------------------------------*/
	
	/**
	* Internal: Reset arrays
	*
	* @return	void
	*/
	function _kill_image_elements()
	{
		$this->rss_img_image  = "";
		$this->rss_img_title  = "";
		$this->rss_img_link   = "";
		$this->rss_img_width  = "";
		$this->rss_img_height = "";
		$this->rss_img_desc   = "";
	}
	
	/*-------------------------------------------------------------------------*/
	// Kill channel elements
	/*-------------------------------------------------------------------------*/
	
	/**
	* Internal: Reset arrays
	*
	* @return	void
	*/
	function _kill_channel_elements()
	{
		$this->rss_chan_title = "";
		$this->rss_chan_link  = "";
		$this->rss_chan_desc  = "";
		$this->rss_chan_date  = "";
	}

}

?>