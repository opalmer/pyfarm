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
|   > $Date: 2007-06-26 11:58:06 -0400 (Tue, 26 Jun 2007) $
|   > $Revision: 1066 $
|   > $Author: matt $
+---------------------------------------------------------------------------
|
|   > API: Core functions
|   > Module written by Matt Mecham
|   > Date started: Wednesday 19th July 2005 (14:57)
|
+--------------------------------------------------------------------------
*/

/**
* API: CORE
*
* This class deals with all core functions.
* This class deals with all available language functions.
* If $ipsclass is passed to it, then it will skip the init
* However, if $ipsclass is not an object, it'll initialize
* the $ipsclass object, and by default load all the required
* IPB classes and init them (DB, converge, forums, display, session)
* and it'll also get a new DB connection and load settings and caches.
* You can alter which classes are loaded via the $this->load_classes[-class-] = 1;
* array
*
* @package		InvisionPowerBoard
* @subpackage	APIs
* @author		Matt Mecham
* @copyright	Invision Power Services, Inc.
* @version		2.1
*/

/**
* API: CORE
*
* This class deals with all core functions.
*
* @package		InvisionPowerBoard
* @subpackage	APIs
* @author  	 	Matt Mecham
* @version		2.1
* @since		2.1.0
*/
class api_core
{
	/**
	* IPS Class Object
	*
	* @var object
	*/
	var $ipsclass;
	
	/**
	* API init run
	*
	* @var integer boolean
	*/
	var $init_run;
	
	/**
	* API Error string
	*
	* @var array Errors
	*/
	var $api_error = array();
	
	/**
	* API Load full?
	*
	* @var integer boolean
	*/
	var $full_load = 1;
	
	/**
	* API Path to IPB root (where init.php/index.php is)
	*
	* @var string Path to IPB root folder
	*/
	var $path_to_ipb = '../../';
	
	/**
	* API Load classes
	*
	* @var array Classes to load default: [print,forums,converge,session]
	*/
	var $load_classes = array( 'print'    => 1,
							   'forums'   => 1,
							   'converge' => 1,
							   'session'  => 1 );
							   
	/*-------------------------------------------------------------------------*/
	// API init
	/*-------------------------------------------------------------------------*/
	/**
	* Determines if we have required ipsclass vars
	* on hand
	*
	* @return void;
	*/
	function api_init()
	{
		//-------------------------------
		// Already loaded?
		//-------------------------------
		
		if ( $this->init_run OR ! $this->full_load )
		{
			return;
		}
		
		//-------------------------------
		// INIT run?
		//-------------------------------
		
		if ( ! defined( 'IPB_INIT_DONE' ) )
		{
			define( 'IPB_THIS_SCRIPT', 'api' );
			
			require_once( $this->path_to_ipb.'init.php' );
		}
		
		//-------------------------------
		// IPS Class an object?
		//-------------------------------
		
		if ( ! is_object( $this->ipsclass ) )
		{
			$INFO = array();

			//--------------------------------
			// Load our classes
			//--------------------------------
			
			require_once ROOT_PATH   . "sources/ipsclass.php";
			require_once ROOT_PATH   . "conf_global.php";
			
			# Initiate super-class
			$ipsclass       = new ipsclass();
			$ipsclass->vars = $INFO;
			
			$this->ipsclass           =& $ipsclass;
			$this->ipsclass->base_url = $this->ipsclass->vars['board_url'].'/index.'.$this->ipsclass->vars['php_ext'].'?';
		}
	
		//-------------------------------
		// DB connected?
		//-------------------------------
		
		if ( ! defined( 'IPSCLASS_DB_LOADED' ) )
		{
			$this->ipsclass->vars['sql_force_new_connection'] = 1;
			$this->ipsclass->init_db_connection();
		}
		
		//-------------------------------
		// DISPLAY Object?
		//-------------------------------
		
		if ( ! is_object( $this->ipsclass->print ) AND $this->load_classes['print'] )
		{
			require_once ROOT_PATH   . "sources/classes/class_display.php";
			
			$this->ipsclass->print            =  new display();
			$this->ipsclass->print->ipsclass  =& $this->ipsclass;
		}
		
		//-------------------------------
		// SESSION Object?
		//-------------------------------
		
		if ( ! is_object( $this->ipsclass->sess ) AND $this->load_classes['session'] )
		{
			require_once ROOT_PATH   . "sources/classes/class_session.php";
			
			$this->ipsclass->sess             =  new session();
			$this->ipsclass->sess->ipsclass   =& $this->ipsclass;
		}
		
		//-------------------------------
		// Converge Object?
		//-------------------------------
		
		if ( ! is_object( $this->ipsclass->converge ) AND $this->load_classes['converge'] )
		{
			require_once KERNEL_PATH . "class_converge.php";
			
			$this->ipsclass->converge = new class_converge( $this->ipsclass->DB );
		}
		
		//-------------------------------
		// Caches init?
		//-------------------------------
		
		if ( ! defined('IPSCLASS_CACHE_LOADED') )
		{
			$this->ipsclass->init_load_cache( array('rss_export', 'bbcode', 'attachtypes', 'badwords', 'emoticons', 'settings', 'group_cache', 'systemvars', 'skin_id_cache', 'languages', 'forum_cache', 'moderators', 'stats') );
		}
		
		//-------------------------------
		// FORUMS Object?
		//-------------------------------
		
		if ( ! is_object( $this->ipsclass->forums ) AND $this->load_classes['forums'] )
		{
			require_once ROOT_PATH   . "sources/classes/class_forums.php";
			
			$this->ipsclass->forums           =  new forum_functions();
			$this->ipsclass->forums->ipsclass =& $this->ipsclass;
			$this->ipsclass->forums->strip_invisible = 1;
			$this->ipsclass->forums->forums_init();
		}
		
		if ( ! defined('IPSCLASS_INITIATED') )
		{
			$this->ipsclass->initiate_ipsclass();
			$this->ipsclass->md5_check = $ipsclass->return_md5_check();
			$this->ipsclass->parse_incoming();
			
			$this->ipsclass->load_skin();
		}
		
		$this->init_run = 1;
	}
}








?>