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
|   > XML ARCHIVE CLASS: Handling methods (KERNEL)
|   > Module written by Matt Mecham
|   > Date started: 25th February
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/

/**
* IPS Kernel Pages: XMLArchive
*
* This class contains all generic functions to handle
* the creation and extraction of XML Archive documents
* This is a proprietry technology of IPS, Inc.
*
* Example Usage:
* <code>
* 
* $xmlarchive = new class_xmlarchive();
* 
* $xmlarchive->xml_add_file( 'some/dir/filename.html' );
* $xmlarchive->xml_add_file_contents( $data, 'some/dir/filename.html' );
* 
* $xmlarchive->xml_add_file( 'some/dir/filename.html', array( 'custom_tag' => $value ) );
* $xmlarchive->xml_add_file_contents( $data, 'some/dir/filename.html', array( 'custom_tag' => $value ) );
* 
* $xmlarchive->xml_add_directory( 'some/dir' );
* $xmlarchive->xml_create_archive();
* 
* $xmlarchive->xml_save( 'filename.xml' );
* $xmlarchive->xml_save_gzip( 'filename.xml.gz' );
* 
* $contents = $xmlarchive->xml_get_contents();
* 
* $xmlarchive->xml_read_archive( 'filename.xml' );
* $xmlarchive->xml_read_archive_data( $xml_data );
* </code>
* ERROR NUMBERS:
* <ul>
* <li>001: No such directory or file
* <li>002: Not a directory
* <li>003: Could not write archive to disk
* <li>004: No XML document to save
* <li>005: Could not load xml data
* </ul>
*
* @package		IPS_KERNEL
* @author		Matt Mecham
* @copyright	Invision Power Services, Inc.
* @version		2.1
*/

/**
* XMLArchive Creation and Extraction Functions
*
* Methods and functions for handling XMLArchive documents
*
* @package		IPS_KERNEL
* @author  		Matt Mecham
* @version		2.1
*/

class class_xmlarchive
{
	/**
	* XML content
	*
	* @var string
	*/
	var $xml           = "";
	
	/**
	* File array
	*
	* @var artay
	*/
	var $file_array    = array();
	
	/**
	* Error number
	*
	* @var integer
	*/
	var $error_number  = 0;
	
	/**
	* Error message
	*
	* @var string
	*/
	var $error_message = "";
	
	/**
	* Work files
	*
	* @var array
	*/
	var $workfiles     = array();
	
	/**
	* Non binary file extensions
	*
	* @var string
	*/
	var $non_binary    = 'txt htm html xml css js cgi php php3';
	
	/**
	* Strip path
	*
	* @var string
	*/
	var $strip_path    = "";
	
	/**
	* Root path
	*
	* @var string
	*/
	var $root_path     = "";
	
	/*-------------------------------------------------------------------------*/
	// CONSTRUCTOR
	/*-------------------------------------------------------------------------*/
	
	function class_xmlarchive( $root_path="" )
	{
		$tmp             = "";
		$this->root_path = $root_path;
		
		//-----------------------------------
		// Get the XML class
		//-----------------------------------
		
		require_once( $this->root_path.'class_xml.php' );
		
		$this->xml = new class_xml();
		
		$this->error_number = 0;
	}
	
	/*-------------------------------------------------------------------------*/
	// READ Archive from disk
	/*-------------------------------------------------------------------------*/
	
	/**
	* Read an XML document from disk
	*
	* @param	string	File name
	* @return	void
	*/
	function xml_read_archive( $filename )
	{
		if ( file_exists( $filename ) )
		{
			if ( strstr( $filename, '.gz' ) )
			{
				if ( $FH = @gzopen( $filename, 'r' ) )
				{
					$data = @gzread( $FH, $filename );
					@gzclose( $FH );
				}
				else
				{
					$this->error_number = '005';
				}
			}
			else
			{
				if ( $FH = @fopen( $filename, 'r' ) )
				{
					$data = @fread( $FH, filesize( $filename ) );
					@fclose( $FH );
				}
				else
				{
					$this->error_number = '005';
				}
			}
			
			$this->xml_read_archive_data( $data );
		}
		else
		{
			$this->error_number = '001';
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// READ Archive from data
	/*-------------------------------------------------------------------------*/
	
	/**
	* Read an XML document from passed data
	*
	* @param	string	Raw XML Data
	* @return	void
	*/
	function xml_read_archive_data( $data )
	{
		if ( $data )
		{
			$this->xml->xml_parse_document( $data );
			
			if ( is_array( $this->xml->xml_array ) )
			{
				$this->file_array = array();
				
				foreach( $this->xml->xml_array['xmlarchive']['fileset']['file'] as $entry )
				{
					$this_array = array();
					
					if( is_array( $entry ) )
					{
						foreach ( $entry as $k => $v )
						{
							if ( $k == 'content' )
							{
								$v['VALUE'] = base64_decode( preg_replace( "/\s/", "", $entry['content']['VALUE'] ) );
							}
							
							$this_array[ $k ] = $v['VALUE'];
						}
						
						$this->file_array[] = $this_array;
					}
				}
			}
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// SAVE XML to disk
	/*-------------------------------------------------------------------------*/
	
	/**
	* Write out the XML document as a GZIP file
	*
	* @param	string	FIlename
	* @return	void
	*/
	function xml_save_gzip( $filename )
	{
		if ( $this->xml->xml_document )
		{
			if ( $FH = @gzopen( $filename, 'wb' ) )
			{
				@gzwrite( $FH, $this->xml->xml_document );
				@gzclose( $FH );
			}
			else
			{
				$this->error_number = '003';
			}
		}
		else
		{
			$this->error_number = '004';
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// SAVE XML to disk
	/*-------------------------------------------------------------------------*/
	
	/**
	* Write out the XML document as a normal file
	*
	* @param	string	FIlename
	* @return	void
	*/
	function xml_save( $filename )
	{
		if ( $this->xml->xml_document )
		{
			if ( $FH = @fopen( $filename, 'wb' ) )
			{
				@fwrite( $FH, $this->xml->xml_document );
				@fclose( $FH );
			}
			else
			{
				$this->error_number = '003';
			}
		}
		else
		{
			$this->error_number = '004';
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// XML get contents
	/*-------------------------------------------------------------------------*/
	
	/**
	* Method of getting XML document from this class
	*
	* @return	string	XML document
	*/
	function xml_get_contents()
	{
		return $this->xml->xml_document;
	}
	
	/*-------------------------------------------------------------------------*/
	// Create the XML archive
	/*-------------------------------------------------------------------------*/
	
	/**
	* Create the XML archive
	*
	* @return	void
	*/
	function xml_create_archive()
	{
		$this->xml->xml_set_root( 'xmlarchive', array( 'generator' => 'IPB', 'created' => time() ) );
		$this->xml->xml_add_group( 'fileset' );
		
		$entry = array();
		
		foreach( $this->file_array as $f )
		{
			$content = array();
			
			foreach ( $f as $k => $v )
			{
				if ( $k == 'content' )
				{
					$v = chunk_split(base64_encode($v));
				}
				
				$content[] = $this->xml->xml_build_simple_tag( $k, $v );
			}
			
			$entry[]   = $this->xml->xml_build_entry( 'file', $content );
		}
		
		$this->xml->xml_add_entry_to_group( 'fileset', $entry );
		
		$this->xml->xml_format_document();
	}
	
	/*-------------------------------------------------------------------------*/
	// Add directory contents
	/*-------------------------------------------------------------------------*/
	
	function xml_add_directory( $dir )
	{
		$this->error_number = "";
		
		//-----------------------------------
		// Got dir?
		//-----------------------------------
		
		if ( ! is_dir($dir) )
		{
			$this->error_number = '001';
			return FALSE;
		}
		
		//-----------------------------------
		// Populate this->workfiles
		//-----------------------------------
		
		$this->workfiles = array();
		$this->_xml_get_dir_contents( $dir );
		
		//-----------------------------------
		// Add them into the file array
		//-----------------------------------
		
		foreach ( $this->workfiles as $f )
		{
			$this->xml_add_file( $f );
		}
		
		$this->workfiles = array();
		
	}
	
	/*-------------------------------------------------------------------------*/
	// Add File (interface to file_contents)
	/*-------------------------------------------------------------------------*/
	
	function xml_add_file( $filename, $extra_tags=array() )
	{
		//-----------------------------------
		// Kill OS X hidden files
		//-----------------------------------
		
		if ( preg_match( "/\.ds_store/i", $filename ) )
		{
			return;
		}
		
		if ( file_exists( $filename ) )
		{
			if ( $FH = @fopen( $filename, 'rb' ) )
			{
				$data = @fread( $FH, filesize( $filename ) );
				@fclose( $FH );
			}
			
			$this->xml_add_file_contents( $data, $filename, $extra_tags );
		}
		else
		{
			$this->error_number = '001';
		}
	}

	
	/*-------------------------------------------------------------------------*/
	// Add File Contents
	/*-------------------------------------------------------------------------*/
	
	function xml_add_file_contents( $data, $filename, $extra_tags=array() )
	{
		$ext = preg_replace( "/.*\.(.+?)$/", "\\1", $filename );
		
		$binary = 1;
		
		//-----------------------------------
		// ASCII?
		//-----------------------------------
		
		if ( strstr( ' '.$this->non_binary.' ', ' '.$ext.' ' ) )
		{
			$binary = 0;
		}
		
		//-----------------------------------
		// Get dir / filename
		//-----------------------------------
		
		$dir_path = array();
		$dir_path = explode( "/", $filename );
		
		if ( count( $dir_path ) )
		{
			$real_filename = array_pop( $dir_path );
		}
		
		$real_filename = $real_filename ? $real_filename : $filename;
		
		$path = implode( "/", $dir_path );
		
		if ( $this->strip_path )
		{
			$path = preg_replace( "#". preg_quote($this->strip_path, '#')."/?#", "", $path );
		}
		
		$this_array = array(
							'filename' => $real_filename,
							'content'  => $data,
							'path'     => $path,
							'binary'   => $binary
						  );
						  
		foreach( $extra_tags as $k => $v )
		{
			if ( $k and ! in_array( $k, array_keys($this_array) ) )
			{
				$this_array[ $k ] = $v;
			}
		}
		
		$this->file_array[] = $this_array;
	}
	
	/*-------------------------------------------------------------------------*/
	// INTERNAL: Get directory contents
	/*-------------------------------------------------------------------------*/
	
	function _xml_get_dir_contents( $dir )
	{
		$dir = preg_replace( "#/$#", "", $dir );
		
		if ( file_exists($dir) )
		{
			if ( is_dir($dir) )
			{
				$handle = opendir($dir);
				
				while (($filename = readdir($handle)) !== false)
				{
					if (($filename != ".") && ($filename != ".."))
					{
						if ( is_dir( $dir."/".$filename ) )
						{
							//-----------------------------------
							// Recurse
							//-----------------------------------
							
							$this->_xml_get_dir_contents($dir."/".$filename);
						}
						else
						{
							//-----------------------------------
							// Add file to list
							//-----------------------------------
							
							$this->workfiles[] = $dir."/".$filename;
						}
					}
				}
				
				closedir($handle);
			}
			else
			{
				$this->error_number = '002';
				return FALSE;
			}
		}
		else
		{
			$this->error_number = '001';
			return;
		}
	}

}



?>