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
|   > $Date: 2007-05-15 17:56:01 -0400 (Tue, 15 May 2007) $
|   > $Revision: 997 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > API: Languages
|   > Module written by Matt Mecham
|   > Date started: Wednesday 19th July 2005 (14:33)
|
+--------------------------------------------------------------------------
*/

/**
* API: Languages
*
* EXAMPLE USAGE
* <code>
* $api =  new api_language();
* # Optional - if $ipsclass is not passed, it'll init
* $api->ipsclass =& $this->ipsclass;
* $api->api_init();
* $api->lang_add_strings( array('lang_key' => "Language value" ), 'lang_subscriptions' );
* </code>
*
* @package		InvisionPowerBoard
* @subpackage	APIs
* @author		Matt Mecham
* @copyright	Invision Power Services, Inc.
* @version		2.1
*/

if ( ! defined( 'IPS_API_PATH' ) )
{
	/**
	* Define classes path
	*/
	define( 'IPS_API_PATH', dirname(__FILE__) ? dirname(__FILE__) : '.' );
}

if ( ! class_exists( 'api_core' ) )
{
	require_once( IPS_API_PATH.'/api_core.php' );
}

/**
* API: Languages
*
* This class deals with all available language functions.
*
* @package		InvisionPowerBoard
* @subpackage	APIs
* @author  	 	Matt Mecham
* @version		2.1
* @since		2.1.0
*/
class api_language extends api_core
{
	/**
	* IPS Class Object
	*
	* @var object
	*/
	//var $ipsclass;
	
	
	/*-------------------------------------------------------------------------*/
	// Add language strings to IPB language system
	/*-------------------------------------------------------------------------*/
	/**
	* Add language strings to the IPB language system
	*
	* @param	array	Language keys => values to add
	* @param	string	Language file, eg: lang_global
	* @param	string	Language pack to add to or 'all' to add to all
	* @return void;
	*/
	function lang_add_strings( $to_add=array(), $add_lang_file='', $add_where='all')
	{
		//-------------------------------
		// Check?
		//-------------------------------
		
		if ( ! count( $to_add ) )
		{
			$this->api_error[] = "input_missing_fields";
			return;
		}
		
		if ( ! $add_lang_file )
		{
			$this->api_error[] = "input_missing_fields";
			return;
		}
		
		//-------------------------------
		// Trim off .php
		//-------------------------------
		
		$add_lang_file = str_replace( '.php', '', $add_lang_file );
		
		//-------------------------------
		// Get lang stuff from DB
		//-------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'languages' ) );
		$o = $this->ipsclass->DB->simple_exec();
		
		//-------------------------------
		// Go loopy
		//-------------------------------
		
		while( $row = $this->ipsclass->DB->fetch_row( $o ) )
		{
			$lang = array();
			
			if ( $add_where == $row['ldir'] OR $add_where == 'all' )
			{
				$lang_file = CACHE_PATH."cache/lang_cache/".$row['ldir']."/".$add_lang_file.'.php';
				
				if ( file_exists( $lang_file ) )
				{
					require ( $lang_file );
				}
				else
				{
					$this->api_error[] = "file_not_found";
					return;
				}
				
				foreach( $to_add as $k => $v )
				{
					$lang[ $k ] = $v;
				}
				
				//-------------------------------
				// Write output
				//-------------------------------
				
				$start = "<?php\n\n".'$lang = array('."\n";
		
				foreach( $lang as $key => $text)
				{
					$text   = preg_replace("/\n{1,}$/", "", $text);
					$text 	= stripslashes($text);
					$text	= preg_replace( '/"/', '\\"', $text );
					$start .= "\n'".$key."'  => \"".$text."\",";
				}
				
				$start .= "\n\n);\n\n?".">";
				
				if ( $fh = @fopen( $lang_file, 'w') )
				{
					fwrite($fh, $start );
					fclose($fh);
				}
				else
				{
					$this->api_error[] = "file_not_writeable";
					continue;
				}
			}
		}
	}
	
	
	
	
}



?>