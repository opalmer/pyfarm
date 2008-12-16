<?php

/*
+--------------------------------------------------------------------------
|   Invision Power Board
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2004 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Email: matt@invisionpower.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
|
|   > IPB UPGRADE MODULE:: IPB 2.0.0 PF3 -> PF 4
|   > Script written by Matt Mecham
|   > Date started: 23rd April 2004
|   > "So what, pop is dead - it's no great loss.
	   So many facelifts, it's face flew off"
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class version_upgrade
{
	var $install;
	
	/*-------------------------------------------------------------------------*/
	// CONSTRUCTOR
	/*-------------------------------------------------------------------------*/
	
	function version_upgrade( & $install )
	{
		$this->install = & $install;
	}
	
	/*-------------------------------------------------------------------------*/
	// Auto run..
	/*-------------------------------------------------------------------------*/

	function auto_run()
	{
		//--------------------------------
		// Upgrade BBCode
		//--------------------------------
		
		require_once( ROOT_PATH . 'sources/action_admin/bbcode.php' );
		$bbcode           =  new ad_bbcode();
		$bbcode->ipsclass =& $this->install->ipsclass;
		
		//-----------------------------------------
		// Get BBCode....
		//-----------------------------------------
		
		if ( $FH = @fopen( ROOT_PATH . 'resources/bbcode.xml', 'rb' ) )
		{
			$content = @fread( $FH, filesize( ROOT_PATH . 'resources/bbcode.xml' ) );
			@fclose( $FH );
		}
		
		//-----------------------------------------
		// Get xml mah-do-dah
		//-----------------------------------------
		
		require_once( KERNEL_PATH.'class_xml.php' );

		$xml = new class_xml();
		$xml->lite_parser = 1;
		
		//-----------------------------------------
		// Unpack the datafile
		//-----------------------------------------
		
		$xml->xml_parse_document( $content );
		
		//-----------------------------------------
		// Get current custom bbcodes
		//-----------------------------------------
		
		$tags = array();
		
		$this->install->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'custom_bbcode' ) );
		$this->install->ipsclass->DB->simple_exec();
		
		while ( $r = $this->install->ipsclass->DB->fetch_row() )
		{
			$tags[ $r['bbcode_tag'] ] = 1;
		}
		
		//-----------------------------------------
		// pArse
		//-----------------------------------------
		
		if ( ! is_array( $xml->xml_array['bbcodeexport']['bbcodegroup']['bbcode'][0]  ) )
		{
			$xml->xml_array['bbcodeexport']['bbcodegroup']['bbcode'][0] = $xml->xml_array['bbcodeexport']['bbcodegroup']['bbcode'];
		}
		
		foreach( $xml->xml_array['bbcodeexport']['bbcodegroup']['bbcode'] as $entry )
		{
			$bbcode_title             = $entry['bbcode_title']['VALUE'];
			$bbcode_desc              = $entry['bbcode_desc']['VALUE'];
			$bbcode_tag               = $entry['bbcode_tag']['VALUE'];
			$bbcode_replace           = $entry['bbcode_replace']['VALUE'];
			$bbcode_useoption         = $entry['bbcode_useoption']['VALUE'];
			$bbcode_example           = $entry['bbcode_example']['VALUE'];
			$bbcode_switch_option     = $entry['bbcode_switch_option']['VALUE'];
			$bbcode_add_into_menu     = $entry['bbcode_add_into_menu']['VALUE'];
			$bbcode_menu_option_text  = $entry['bbcode_menu_option_text']['VALUE'];
			$bbcode_menu_content_text = $entry['bbcode_menu_content_text']['VALUE'];
			
			if ( $tags[ $bbcode_tag ] )
			{
				$bbarray = array(
								 'bbcode_title'             => $bbcode_title,
								 'bbcode_desc'              => $bbcode_desc,
								 'bbcode_tag'               => $bbcode_tag,
								 'bbcode_replace'           => $this->install->ipsclass->txt_safeslashes($bbcode_replace),
								 'bbcode_useoption'         => $bbcode_useoption,
								 'bbcode_example'           => $bbcode_example,
								 'bbcode_switch_option'     => $bbcode_switch_option,
								 'bbcode_add_into_menu'     => $bbcode_add_into_menu,
								 'bbcode_menu_option_text'  => $bbcode_menu_option_text,
								 'bbcode_menu_content_text' => $bbcode_menu_content_text,
								);
								
				$this->install->ipsclass->DB->do_update( 'custom_bbcode', $bbarray, "bbcode_tag='".$bbcode_tag."'" );
				
				continue;
			}
			
			if ( $bbcode_tag )
			{
				$bbarray = array(
								 'bbcode_title'             => $bbcode_title,
								 'bbcode_desc'              => $bbcode_desc,
								 'bbcode_tag'               => $bbcode_tag,
								 'bbcode_replace'           => $this->install->ipsclass->txt_safeslashes($bbcode_replace),
								 'bbcode_useoption'         => $bbcode_useoption,
								 'bbcode_example'           => $bbcode_example,
								 'bbcode_switch_option'     => $bbcode_switch_option,
								 'bbcode_add_into_menu'     => $bbcode_add_into_menu,
								 'bbcode_menu_option_text'  => $bbcode_menu_option_text,
								 'bbcode_menu_content_text' => $bbcode_menu_content_text,
								);
								
				$this->install->ipsclass->DB->do_insert( 'custom_bbcode', $bbarray );
			}
		}
		
		$bbcode->bbcode_rebuildcache();
		
		$this->install->message = "BBCode rebuilt and recached...";
		
		return true;
	}
	
}
	
	
?>