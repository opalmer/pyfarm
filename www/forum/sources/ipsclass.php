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
|   > Multi function library
|   > Module written by Matt Mecham
|   > Date started: 14th February 2002
|
|	> Module Version Number: 1.0.0
|   > Quality Checked: Wed 15 Sept. 2004
+--------------------------------------------------------------------------
*/

/**
* Main IPSclass class.
*
* This is the main class which is referenced via $ipsclass throughout
* all the IPB sub-classes and modules. It holds amongst others:
* <code>
* // Object from class_forums.php class
* $ipsclass->forums
* // Object from class_display.php class
* $ipsclass->print
* // Object from SQL classes
* $ipsclass->db
* // Array of parsed caches  (from table cache_store)
* $ipsclass->cache
* // Array of settings variables (taken from parsed setting cache)
* $ipsclass->vars
* // Array of member variables (taken from class_session::$member)
* $ipsclass->member
* // Array of sanitized _GET _POST data (run via parse_clean_value)
* $ipsclass->input
* // Array of language strings loaded via separate lang files
* $ipsclass->lang
* // Variable: Full base URL with session ID if required
* $ipsclass->base_url
* // Array of compiled template objects
* $ipsclass->compiled_templates
* </code>
* Recommended method of passing $ipsclass through modules
* <code>
* $module           = new module();
* $module->ipsclass =& $ipsclass; // Create reference
* </code>
*
* @package		InvisionPowerBoard
* @author		Matt Mecham
* @copyright	Invision Power Services, Inc.
* @version		2.1
*/

if ( ! defined( 'IPSCLASS_LOADED' ) )
{
	/**
	* IPSCLASS loaded Flag
	*/
	define( 'IPSCLASS_LOADED', 1 );
}

/**
* Main ipsclass class.
*
* This class holds all the non-class specific functions
*
* @package	InvisionPowerBoard
* @author   Matt Mecham
* @version	2.1
*/
class ipsclass {

	/**
	* HUMAN version string (Eg: v2.1 BETA 1)
	*
	* @var string
	*/
	var $version      = IPBVERSION;
	
	/**
	* LONG version number (Eg: 21000.BUILD_DATE.REASON)
	*
	* @var string
	*/
	var $acpversion      = IPB_LONG_VERSION;
	var $vn_full         = '';
	var $vn_build_date   = '';
	var $vn_build_reason = '';
	
	/**
	* Member Array
	*
	* @var array
	*/
	var $member             = array();
	
	/**
	* _GET _POST Input Array
	*
	* @var array
	*/
	var $input              = array( 'st' 			=> 0,
									 't'			=> 0,
									 'f'			=> 0,
									 'id'			=> 0,
									 'tid'			=> 0,
									 'p'			=> 0,
									 'pid'			=> 0,
									 'gopid'		=> 0,
									 'qpid'			=> 0,
									 'b'			=> 0,
									 'cal_id'		=> 0,
									 'act'			=> NULL,
									 'L'			=> 0,
									 'adsess'		=> NULL,
									 'code'			=> NULL,
									 'CODE'			=> NULL,
									 'mode'			=> NULL,
									 'modfilter'	=> NULL,
									 'preview'		=> NULL,
									 'attachgo' 	=> NULL,
									 'nocp'			=> NULL,
									 'tab'			=> NULL,
									 'sort'			=> NULL,
									 'dsterror' 	=> 0,
									 'MID'			=> 0,
									 'MSID'			=> 0,
									 'uid'			=> 0,
									 'ip'			=> NULL,
									 'selectedpids'	=> NULL,
									 'nav'			=> NULL,
									 'calendar_id'	=> 0,  );
	
	/**
	* Setting variables array
	*
	* @var array
	*/
	var $vars               = array( );
	
	/**
	* Language strings array
	*
	* @var array
	*/
	var $lang               = array();
	
	/**
	* Skin variables array
	*
	* @var array
	*/
	var $skin               = array();
	
	/**
	* Compiled loaded templates
	*
	* @var array
	*/
	var $compiled_templates = array();
	
	/**
	* Loaded templates inc. ID
	*
	* @var array
	*/
	var $loaded_templates = array();
	
	/**
	* Cache array
	*
	* @var array
	*/
	var $cache              = array();
	
	/**
	* Cache Library
	*
	* @var array
	*/	
	var $cachelib;
	
	/**
	* Session ID
	*
	* @var string
	*/
	var $session_id         = "";
	
	/**
	* Base URL
	*
	* @var string
	*/
	var $base_url           = "";
	
	/**
	* Language ID; corresponds to cache/lang_cache/{folder}/
	*
	* @var string
	*/
	var $lang_id            = "";
	
	/**
	* Session type (cookie or url)
	*
	* @var string
	*/
	var $session_type       = "";
	
	/**#@+
	* @access public
	* @var string 
	*/
	var $lastclick               = "";
	var $location                = "";
	var $debug_html              = "";
	var $perm_id                 = "";
	var $offset                  = "";
	var $num_format              = "";
	var $query_string_safe       = '';
	var $query_string_formatted  = '';
	
	/**
	* MD5 Check variable
	*/
	var $md5_check          = '';
	/**#@-*/
	
	var $server_load        = 0;
	var $can_use_fancy_js   = 0;
	var $force_editor_change = 0;
	var $offset_set         = 0;
	var $allow_unicode      = 1;
	var $get_magic_quotes   = 0;
	var $no_print_header	= 0;
	
	var $today_time;
	var $yesterday_time;
	
	/**#@+
	* @access public
	* @var object 
	*/
	var $converge;
	var $print;
	var $sess;
	var $forums;
	var $class_convert_charset;
	/**#@-*/

	/**#@+
	* @access public
	* @var array 
	*/
	var $topic_cache      = array();
	var $time_formats     = array();
	var $time_options     = array();
	var $today_array      = array();
	var $forum_read		  = array();
	var $my_group_helpkey = null;
	var $perm_id_array    = array();
	
	/**
	* User's browser array (version, browser)
	*/
	var $browser;
	var $is_bot				= 0;
	/**#@-*/
	
	var $main_msg			= '';
	var $html_help_msg		= '';
	var $html_help_title	= '';
	var $kill_menu			= 0;
	var $body_extra			= '';
	
	var $parsed_members = array();
	
	var $work_classes       = array( 'class_template_engine' => null,
									 'class_captcha'         => null );
	
	/**
	* Sensitive cookies
	* var array
	*/
	var $sensitive_cookies  = array( 'ipb_stronghold', 'session_id', 'ipb_admin_session_id', 'member_id', 'pass_hash' );
									
	/*-------------------------------------------------------------------------*/
	// Set up some standards to save CPU later
	/*-------------------------------------------------------------------------*/
	
	/**
	* Initial ipsclass, set up some variables for later
	* Populates:
	* $this->time_options, $this->num_format, $this->get_magic_quotes, $this->ip_address
	* $this->user_agent, $this->browser, $this->operating_system
	*
	* @return void;
	*/
	function initiate_ipsclass()
	{
		//-----------------------------------------
		// Version numbers
		//-----------------------------------------
		
		//$this->acpversion = '210015.060501.u';
		
		if ( strstr( $this->acpversion , '.' ) )
		{
			list( $n, $b, $r ) = explode( ".", $this->acpversion );
		}
		else
		{
			$n = $b = $r = '';
		}
		
		$this->vn_full         = $this->acpversion;
		$this->acpversion      = $n;
		$this->vn_build_date   = $b;
		$this->vn_build_reason = $r;
		
		//-----------------------------------------
		// Time options
		//-----------------------------------------
		
		$this->time_options = array( 'JOINED' => $this->vars['clock_joined'],
									 'SHORT'  => $this->vars['clock_short'],
									 'LONG'   => $this->vars['clock_long'],
									 'TINY'   => isset($this->vars['clock_tiny']) ? $this->vars['clock_tiny'] : 'j M Y - G:i',
									 'DATE'   => isset($this->vars['clock_date']) ? $this->vars['clock_date'] : 'j M Y',
								   );
								   
		$this->num_format = ( $this->vars['number_format'] == 'space' ) ? ' ' : $this->vars['number_format'];
		
		//-----------------------------------------
		// Sort out the accessing IP
		// (Thanks to Cosmos and schickb)
		//-----------------------------------------
		
		$addrs = array();
		
		if ( $this->vars['xforward_matching'] )
		{
			foreach( array_reverse( explode( ',', $this->my_getenv('HTTP_X_FORWARDED_FOR') ) ) as $x_f )
			{
				$x_f = trim($x_f);
				
				if ( preg_match( '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $x_f ) )
				{
					$addrs[] = $x_f;
				}
			}
			
			$addrs[] = $this->my_getenv('HTTP_CLIENT_IP');
			$addrs[] = $this->my_getenv('HTTP_X_CLUSTER_CLIENT_IP');
			$addrs[] = $this->my_getenv('HTTP_PROXY_USER');
		}
		
		$addrs[] = $this->my_getenv('REMOTE_ADDR');
		
		//-----------------------------------------
		// Do we have one yet?
		//-----------------------------------------
		
		foreach ( $addrs as $ip )
		{
			if ( $ip )
			{
				preg_match( "/^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$/", $ip, $match );

				$this->ip_address = $match[1].'.'.$match[2].'.'.$match[3].'.'.$match[4];
				
				if ( $this->ip_address AND $this->ip_address != '...' )
				{
					break;
				}
			}
		}
		
		//-----------------------------------------
		// Make sure we take a valid IP address
		//-----------------------------------------
		 
		if ( ( ! $this->ip_address OR $this->ip_address == '...' ) AND ! isset( $_SERVER['SHELL'] ) )
		{
			print "Could not determine your IP address";
			exit();
		}
		
		#Backwards compat:
		$this->input["IP_ADDRESS"] = $this->ip_address;
		
		//-----------------------------------------
		// Make a safe query string
		//-----------------------------------------
		
		$this->query_string_safe = str_replace( '&amp;amp;', '&amp;', $this->parse_clean_value( urldecode($this->my_getenv('QUERY_STRING')) ) );
		$this->query_string_real = str_replace( '&amp;'    , '&'    , $this->query_string_safe );
		
		//-----------------------------------------
		// Format it..
		//-----------------------------------------
		
		$this->query_string_formatted = str_replace( $this->vars['board_url'] . '/index.'.$this->vars['php_ext'].'?', '', $this->query_string_safe );
		$this->query_string_formatted = preg_replace( "#s=([a-z0-9]){32}#", '', $this->query_string_safe );
		
		//-----------------------------------------
		// Admin dir
		//-----------------------------------------
		
		$this->vars['_admin_link'] = $this->vars['board_url'] . '/' . IPB_ACP_DIRECTORY . '/index.php';
		
		//-----------------------------------------
		//  Upload dir?
		//-----------------------------------------
		
		$this->vars['upload_dir'] = $this->vars['upload_dir'] ? $this->vars['upload_dir'] : ROOT_PATH.'uploads';
		
		//-----------------------------------------
		// Char set
		//-----------------------------------------
		
		$this->vars['gb_char_set'] = $this->vars['gb_char_set'] ? $this->vars['gb_char_set'] : 'iso-8859-1';
		
		//-----------------------------------------
		// Max display name length
		//-----------------------------------------
		
		$this->vars['max_user_name_length'] = $this->vars['max_user_name_length'] ? $this->vars['max_user_name_length'] : 26;
		
		//-----------------------------------------
		// PHP API
		//-----------------------------------------
		
		define('IPB_PHP_SAPI', php_sapi_name() );
		
		//-----------------------------------------
		// IPS CLASS INITIATED
		//-----------------------------------------
		
		if ( ! defined( 'IPSCLASS_INITIATED' ) )
		{
			define( 'IPSCLASS_INITIATED', 1 );
		}
		
		//-----------------------------------------
		// Get user-agent, browser and OS
		//-----------------------------------------
		
		$this->user_agent       = $this->parse_clean_value($this->my_getenv('HTTP_USER_AGENT'));
		$this->browser          = $this->fetch_browser();
		$this->operating_system = $this->fetch_os();
			
		//-----------------------------------------
		// Can we use fancy JS? IE6, Safari, Moz
		// and opera 7.6 
		//-----------------------------------------
		
		if ( $this->browser['browser'] == 'ie' AND $this->browser['version'] >= 6.0 )
		{
			$this->can_use_fancy_js = 1;
		}
		else if ( $this->browser['browser'] == 'gecko' AND $this->browser['version'] >= 20030312 )
		{
			$this->can_use_fancy_js = 1;
		}
		else if ( $this->browser['browser'] == 'mozilla' AND $this->browser['version'] >= 1.3 )
		{
			$this->can_use_fancy_js = 1;
		}
		else if ( $this->browser['browser'] == 'opera' AND $this->browser['version'] >= 7.6 )
		{
			$this->can_use_fancy_js = 1;
		}
		else if ( $this->browser['browser'] == 'safari' AND $this->browser['version'] >= 120)
		{
			$this->can_use_fancy_js = 1;
		}
	
		//$this->can_use_fancy_js = 0;
	}
	
	
	/*-------------------------------------------------------------------------*/
	// INIT: Establish Cache Abstraction
	/*-------------------------------------------------------------------------*/
	
	/**
	* Determines and establishes cache class
	*
	* @todo		Memcache, mmcache, diskcache
	* @param	void
	* @return	void
	*/	
	
	function init_cache_setup()
	{
		//--------------------------------
		// Eaccelerator...
		//--------------------------------
		
		if( function_exists('eaccelerator_get') AND isset($this->vars['use_eaccelerator']) AND $this->vars['use_eaccelerator'] == 1 )
		{
			require KERNEL_PATH.'class_cache_eaccelerator.php';
			$this->cachelib = new cache_lib( $this->vars['board_url'] );
		}
		
		//--------------------------------
		// Turck-mmcache...
		//--------------------------------
		
		if( function_exists('mmcache_get') AND isset($this->vars['use_mmcache']) AND $this->vars['use_mmcache'] == 1 )
		{
			require KERNEL_PATH.'class_cache_mmcache.php';
			$this->cachelib = new cache_lib( $this->vars['board_url'] );
		}		
		
		//--------------------------------
		// Memcache
		//--------------------------------	
			
		else if( function_exists('memcache_connect') AND isset($this->vars['use_memcache']) AND $this->vars['use_memcache'] == 1 )
		{
			require KERNEL_PATH.'class_cache_memcache.php';
			$this->cachelib = new cache_lib( $this->vars['board_url'] );
			
			$this->cachelib->connect( $this->vars );
		}
		
		//--------------------------------
		// XCache...
		//--------------------------------
		
		else if( function_exists('xcache_get') AND isset($this->vars['use_xcache']) AND $this->vars['use_xcache'] == 1 )
		{
			require KERNEL_PATH.'class_cache_xcache.php';
			$this->cachelib = new cache_lib( $this->vars['board_url'] );
		}		
		
		//--------------------------------
		// APC...
		//--------------------------------
		
		else if( function_exists('apc_fetch') AND isset($this->vars['use_apc']) AND $this->vars['use_apc'] == 1 )
		{
			require KERNEL_PATH.'class_cache_apc.php';
			$this->cachelib = new cache_lib( $this->vars['board_url'] );
		}		
		
		//--------------------------------
		// Diskcache
		//--------------------------------	
		
		else if( isset($this->vars['use_diskcache']) AND $this->vars['use_diskcache'] == 1 )
		{
			require KERNEL_PATH.'class_cache_diskcache.php';
			$this->cachelib = new cache_lib( $this->vars['board_url'] );
		}
		
		if( is_object($this->cachelib) AND $this->cachelib->crashed )
		{
			// There was a problem - not installed maybe?
			
			unset($this->cachelib);
			$this->cachelib = NULL;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// INIT: Load cache
	/*-------------------------------------------------------------------------*/
	
	/**
	* Loads and parses the required cache elements from the DB
	*
	* @todo		Add in methods to save and write to disk file
	* @param	array	Array of caches to load
	* @return	void
	*/
	
	function init_load_cache( $cachearray=array('settings', 'group_cache', 'systemvars', 'skin_id_cache', 'forum_cache', 'rss_export') )
	{
		//--------------------------------
		// Eaccelerator...
		//--------------------------------
		
		if( is_object($this->cachelib) )
		{
			$temp_cache 	 = array();
			$new_cache_array = array();
			
			foreach( $cachearray as $key )
			{
				$temp_cache[$key] = $this->cachelib->do_get( $key );
				
				if( !$temp_cache[$key] )
				{
					$new_cache_array[] = $key;
				}
				else
				{
					if ( $key == 'settings' )
					{
						$tmp = unserialize( $temp_cache[$key] );
						
						if ( is_array( $tmp ) and count( $tmp ) )
						{
							foreach( $tmp as $k => $v )
							{
								$this->vars[ $k ] = $v;
							}
						}
						
						if( !isset($this->vars['blog_default_view']) )
						{
							$this->vars['blog_default_view'] = '';
						}
						
						unset( $tmp );
					}
					else
					{
						if ( strstr( $temp_cache[$key], "a:" ) )
						{
							$this->cache[ $key ] = unserialize( $temp_cache[$key] );
						}
						else if( $temp_cache[$key] == "EMPTY" )
						{
							$this->cache[ $key ] = NULL;
						}
						else
						{
							$this->cache[ $key ] = $temp_cache[$key];
						}
					}
				}
			}
			
			$cachearray = $new_cache_array;
			
			unset($new_cache_array, $temp_cache);
		}
		
		//echo "<pre>";print_r($this->cache);exit;
		
		//--------------------------------
		// Generate cache list
		//--------------------------------
		
		$cachelist = "";
		
		if( count($cachearray) )
		{
			$cachelist = "'".implode( "','", $cachearray )."'";
		}
		
		//--------------------------------
		// Get from DB...
		//--------------------------------
		
		if ( $cachelist )
		{
			$this->DB->simple_construct( array( 'select' => '*', 'from' => 'cache_store', 'where' => "cs_key IN ( $cachelist )" ) );
			$this->DB->simple_exec();
			
			while ( $r = $this->DB->fetch_row() )
			{
				if ( $r['cs_key'] == 'settings' )
				{
					$tmp = unserialize( $r['cs_value'] );
					
					if ( is_array( $tmp ) and count( $tmp ) )
					{
						foreach( $tmp as $k => $v )
						{
							$this->vars[ $k ] = $v;
						}
					}
					
					if ( ! isset($this->vars['blog_default_view']) )
					{
						$this->vars['blog_default_view'] = '';
					}
					
					unset( $tmp );
				}
				else
				{
					if ( $r['cs_array'] )
					{
						$this->cache[ $r['cs_key'] ] = unserialize( $r['cs_value'] );
					}
					else
					{
						$this->cache[ $r['cs_key'] ] = $r['cs_value'];
					}
				}
				
				if( is_object($this->cachelib) )
				{
					if( !$r['cs_value'] )
					{
						$r['cs_value'] = "EMPTY";
					}
					
					$this->cachelib->do_put( $r['cs_key'], $r['cs_value'] );
				}
			}
		}
		
		if ( ! isset( $this->cache['systemvars'] ) OR ! isset( $this->cache['systemvars']['task_next_run']) )
		{
			$update 						= array( 'task_next_run' => time() );
			$update['loadlimit'] 			= $this->cache['systemvars']['loadlimit'];
			$update['mail_queue'] 			= $this->cache['systemvars']['mail_queue'];
			$update['last_virus_check'] 	= $this->cache['systemvars']['last_virus_check'];
			$update['last_deepscan_check'] 	= $this->cache['systemvars']['last_deepscan_check'];
			
			$this->update_cache( array( 'deletefirst' => 1, 'donow' => 1, 'name' => 'systemvars', 'array' => 1, 'value' => $update ) );
		}
		
		if( ! isset( $this->cache['forum_cache'] ) OR empty( $this->cache['forum_cache']) )
		{
			$this->update_forum_cache();
		}
		
		if( ! isset( $this->cache['group_cache'] ) OR empty( $this->cache['group_cache']) )
		{
			$this->cache['group_cache'] = array();
		
			$this->DB->simple_construct( array( 'select' => "*",
												 'from'   => 'groups'
										)      );
			
			$this->DB->simple_exec();
			
			while ( $i = $this->DB->fetch_row() )
			{
				$this->cache['group_cache'][ $i['g_id'] ] = $i;
			}
			
			$this->update_cache( array( 'name' => 'group_cache', 'array' => 1, 'deletefirst' => 1 ) );
		}	
		
		//--------------------------------
		// Set up cache path
		//--------------------------------
		
		if( ! defined( 'CACHE_PATH' ) )
		{
			if ( $this->vars['ipb_cache_path'] )
			{
				define( 'CACHE_PATH', $this->vars['ipb_cache_path'] );
			}
			else
			{
				define( 'CACHE_PATH', ROOT_PATH );
			}
		}
		
		//-----------------------------------------
		// IPS CACHE LOADED
		//-----------------------------------------
		
		if ( ! defined( 'IPSCLASS_CACHE_LOADED' ) )
		{
			define( 'IPSCLASS_CACHE_LOADED', 1 );
		}
		
		//--------------------------------
		// Set up defaults
		//--------------------------------
		
		$this->vars['topic_title_max_len'] = $this->vars['topic_title_max_len'] ? $this->vars['topic_title_max_len'] : 50;
		#$this->vars['gb_char_set'] = 'UTF-8';
	}
	
	/*-------------------------------------------------------------------------*/
	// INIT: DB Connection
	/*-------------------------------------------------------------------------*/
	
	/**
	* Initializes the database connection and populates $ipsclass->DB
	*
	* @return void
	*/
	
	function init_db_connection()
	{
		$_pre_load = $this->memory_debug_make_flag();
		
		$this->vars['sql_driver'] = ! $this->vars['sql_driver'] ? 'mysql' : strtolower($this->vars['sql_driver']);
		
		if ( ! class_exists( 'db_main' ) )
		{ 
			require_once( KERNEL_PATH.'class_db.php' );
			require_once( KERNEL_PATH.'class_db_'.$this->vars['sql_driver'].".php" );
		}
		
		$classname = "db_driver_".$this->vars['sql_driver'];
		
		$this->DB = new $classname;
		
		$this->DB->obj['sql_database']         = $this->vars['sql_database'];
		$this->DB->obj['sql_user']             = $this->vars['sql_user'];
		$this->DB->obj['sql_pass']             = $this->vars['sql_pass'];
		$this->DB->obj['sql_host']             = $this->vars['sql_host'];
		$this->DB->obj['sql_tbl_prefix']       = $this->vars['sql_tbl_prefix'];
		$this->DB->obj['force_new_connection'] = isset($this->vars['sql_force_new_connection']) ? $this->vars['sql_force_new_connection'] : 0;
		$this->DB->obj['use_shutdown']         = USE_SHUTDOWN;
		# Error log
		$this->DB->obj['error_log']            = ROOT_PATH . 'cache/sql_error_log_'.date('m_d_y').'.cgi';
		$this->DB->obj['use_error_log']        = IN_DEV ? 0 : 1;
		# Debug log - Don't use this on a production board!
		$this->DB->obj['debug_log']            = ROOT_PATH . 'cache/sql_debug_log_'.date('m_d_y').'.cgi';
		$this->DB->obj['use_debug_log']        = 0;
		
		//-----------------------------------
		// Load query file
		//-----------------------------------
		
		if ( defined( 'IPB_LOAD_SQL' ) )
		{
			$this->DB->obj['query_cache_file'] = ROOT_PATH.'sources/sql/'.$this->vars['sql_driver'].'_'. IPB_LOAD_SQL .'.php';
		}
		else if ( IPB_THIS_SCRIPT == 'admin' )
		{
			$this->DB->obj['query_cache_file'] = ROOT_PATH.'sources/sql/'.$this->vars['sql_driver'].'_admin_queries.php';
		}
		else
		{
			$this->DB->obj['query_cache_file'] = ROOT_PATH.'sources/sql/'.$this->vars['sql_driver'].'_queries.php';
		}
			
		//-----------------------------------
		// Required vars?
		//-----------------------------------
		
		if ( is_array( $this->DB->connect_vars ) and count( $this->DB->connect_vars ) )
		{
			foreach( $this->DB->connect_vars as $k => $v )
			{
				$this->DB->connect_vars[ $k ] = isset($this->vars[ $k ]) ? $this->vars[ $k ] : '';
			}
		}
		
		//--------------------------------
		// Backwards compat
		//--------------------------------
		
		if ( !isset($this->DB->connect_vars['mysql_tbl_type']) OR !$this->DB->connect_vars['mysql_tbl_type'] )
		{
			$this->DB->connect_vars['mysql_tbl_type'] = 'myisam';
		}
		
		//--------------------------------
		// Make CONSTANT
		//--------------------------------
		
		define( 'SQL_PREFIX'              , $this->DB->obj['sql_tbl_prefix'] );
		define( 'SQL_DRIVER'              , $this->vars['sql_driver']        );
		define( 'IPS_MAIN_DB_CLASS_LOADED', TRUE );
		
		//--------------------------------
		// Get a DB connection
		//--------------------------------
		
		$this->DB->connect();
		
		//-----------------------------------------
		// IPS DB LOADED
		//-----------------------------------------
		
		if ( ! defined( 'IPSCLASS_DB_LOADED' ) )
		{
			define( 'IPSCLASS_DB_LOADED', 1 );
		}
		
		//-----------------------------------------
		// Clean up
		//-----------------------------------------
		
		unset( $classname );
		
		$this->memory_debug_add( "CORE: DB Connection Made", $_pre_load );
	}
	
	/*-------------------------------------------------------------------------*/
	// Get browser
	// Return: unknown, windows, mac
	/*-------------------------------------------------------------------------*/
	
	/**
	* Fetches the user's operating system
	*
	* @return	string
	*/
	
	function fetch_os()
	{
		$useragent = strtolower($this->my_getenv('HTTP_USER_AGENT'));
		
		if ( strstr( $useragent, 'mac' ) )
		{
			return 'mac';
		}
		
		if ( preg_match( '#wi(n|n32|ndows)#', $useragent ) )
		{
			return 'windows';
		}
		
		return 'unknown';
	}
	
	/*-------------------------------------------------------------------------*/
	// Get browser
	// Return: unknown, opera, IE, mozilla, konqueror, safari
	/*-------------------------------------------------------------------------*/
	
	/**
	* Fetches the user's browser from their user-agent
	*
	* @return	array [ browser, version ]
	*/
	
	function fetch_browser()
	{
		$version   = 0;
		$browser   = "unknown";
		$useragent = strtolower($this->my_getenv('HTTP_USER_AGENT'));
		
		//-----------------------------------------
		// Opera...
		//-----------------------------------------
		
		if ( strstr( $useragent, 'opera' ) )
		{
			preg_match( "#opera[ /]([0-9\.]{1,10})#", $useragent, $ver );
			
			return array( 'browser' => 'opera', 'version' => $ver[1] );
		}
		
		//-----------------------------------------
		// IE...
		//-----------------------------------------
		
		if ( strstr( $useragent, 'msie' ) )
		{
			preg_match( "#msie[ /]([0-9\.]{1,10})#", $useragent, $ver );
			
			return array( 'browser' => 'ie', 'version' => $ver[1] );
		}
		
		//-----------------------------------------
		// Safari...
		//-----------------------------------------
		
		if ( strstr( $useragent, 'safari' ) )
		{
			preg_match( "#safari/([0-9.]{1,10})#", $useragent, $ver );
			
			return array( 'browser' => 'safari', 'version' => $ver[1] );
		}
		
		//-----------------------------------------
		// Mozilla browsers...
		//-----------------------------------------
		
		if ( strstr( $useragent, 'gecko' ) )
		{ 
			preg_match( "#gecko/(\d+)#", $useragent, $ver );
			
			return array( 'browser' => 'gecko', 'version' => $ver[1] );
		}
		
		//-----------------------------------------
		// Older Mozilla browsers...
		//-----------------------------------------
		
		if ( strstr( $useragent, 'mozilla' ) )
		{
			preg_match( "#^mozilla/[5-9]\.[0-9.]{1,10}.+rv:([0-9a-z.+]{1,10})#", $useragent, $ver );
			
			return array( 'browser' => 'mozilla', 'version' => $ver[1] );
		}
		
		//-----------------------------------------
		// Konqueror...
		//-----------------------------------------
		
		if ( strstr( $useragent, 'konqueror' ) )
		{
			preg_match( "#konqueror/([0-9.]{1,10})#", $useragent, $ver );
			
			return array( 'browser' => 'konqueror', 'version' => $ver[1] );
		}
		
		//-----------------------------------------
		// Still here?
		//-----------------------------------------
		
		return array( 'browser' => $browser, 'version' => $version );
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// LOAD CLASS: Wrapper to load ..er.. classes
	//
	/*-------------------------------------------------------------------------*/
	
	/**
	* Wrapper function to load a class and pass $this automagically
	*
	* @param	string	File name
	* @param	string	Class Name
	* @param	string	Constructor variables
	* @return	object
	*/
	
	function load_class( $file_name, $class_name, $pass_var="" )
	{
		if ( ! $class_name )
		{
			$class_name = $file_name;
		}
		
		require_once( $file_name );
		
		if ( $pass_var )
		{
			$class = new $class_name ( $pass_var );
		}
		else
		{
			$class = new $class_name;
		}
		
		$class->ipsclass =& $this;
		return $class;
	}
	
	/*-------------------------------------------------------------------------*/
	// Stronghold: Check cookie
	/*-------------------------------------------------------------------------*/
	
	/**
	* Checks auto-log in strong hold cookie
	*
	* @param	int     Member's ID
	* @param	string	Member's log in key
	* @return	boolean
	*/
	
	function stronghold_check_cookie( $member_id, $member_log_in_key )
	{
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( ! isset($this->vars['cookie_stronghold']) OR ! $this->vars['cookie_stronghold'] )
		{
			return TRUE;
		}
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$ip_octets  = explode( ".", $this->my_getenv('REMOTE_ADDR') );
		$crypt_salt = md5( $this->vars['sql_pass'].$this->vars['sql_user'] );
		$cookie     = $this->my_getcookie( 'ipb_stronghold' );
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! $cookie )
		{
			return FALSE;
		}
		
		//-----------------------------------------
		// Put it together....
		//-----------------------------------------
		
		$stronghold = md5( md5( $member_id . "-" . $ip_octets[0] . '-' . $ip_octets[1] . '-' . $member_log_in_key ) . $crypt_salt );
		
		//-----------------------------------------
		// Check against cookie
		//-----------------------------------------
		
		return $cookie == $stronghold ? TRUE : FALSE;
	}
	
	/*-------------------------------------------------------------------------*/
	// Stronghold: Create and set cookie
	/*-------------------------------------------------------------------------*/
	
	/**
	* Creates an auto-log in strong hold cookie
	*
	* @param	int     Member's ID
	* @param	string	Member's log in key
	* @return	boolean
	*/
	
	function stronghold_set_cookie( $member_id, $member_log_in_key )
	{
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( ! isset($this->vars['cookie_stronghold']) OR ! $this->vars['cookie_stronghold'] )
		{
			return FALSE;
		}
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$ip_octets  = explode( ".", $this->my_getenv('REMOTE_ADDR') );
		$crypt_salt = md5( $this->vars['sql_pass'].$this->vars['sql_user'] );
		
		//-----------------------------------------
		// Put it together....
		//-----------------------------------------
		
		$stronghold = md5( md5( $member_id . "-" . $ip_octets[0] . '-' . $ip_octets[1] . '-' . $member_log_in_key ) . $crypt_salt );
		
		//-----------------------------------------
		// Set cookie
		//-----------------------------------------
		
		$this->my_setcookie( 'ipb_stronghold', $stronghold, 1 );
	
		return TRUE;
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// Check mod queue status
	//
	/*-------------------------------------------------------------------------*/
	
	/**
	* Determine if this user / forum combo can manage mod queue
	*
	* @param	integer	Forum ID
	* @return	integer Boolean
	*/
	
	function can_queue_posts($fid=0)
	{
		$return = 0;
		
		if ( $this->member['g_is_supmod'] )
		{
			$return = 1;
		}
		else if ( $fid AND isset($this->member['is_mod']) AND $this->member['is_mod'] AND isset($this->member['_moderator'][ $fid ]['post_q']) AND $this->member['_moderator'][ $fid ]['post_q'] )
		{
			$return = 1;
		}
		
		return $return;
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// Check multi mod status
	//
	/*-------------------------------------------------------------------------*/
	
	/**
	* Determine if this user / forum combo can manage multi moderation tasks
	* and return mm_array of allowed tasks
	*
	* @param	integer	Forum ID
	* @return	array	Allowed tasks
	*/
	
	function get_multimod($fid)
	{
		$mm_array = array();
		
		$pass_go = FALSE;
		
		if ( $this->member['id'] )
		{
			if ( $this->member['g_is_supmod'] )
			{
				$pass_go = TRUE;
			}
			else if ( isset($this->member['_moderator'][ $fid ]['can_mm']) AND  $this->member['_moderator'][ $fid ]['can_mm'] == 1 )
			{
				$pass_go = TRUE;
			}
		}
		
		if ( $pass_go != TRUE )
		{
			return $mm_array;
		}
		
		if ( ! array_key_exists( 'multimod', $this->cache ) )
        {
        	$this->cache['multimod'] = array();
        	
			$this->DB->simple_construct( array(
											   'select' => '*',
											   'from'   => 'topic_mmod',
											   'order'  => 'mm_title'
									   )      );
								
			$this->DB->simple_exec();
						
			while ($i = $this->DB->fetch_row())
			{
				$this->cache['multimod'][ $i['mm_id'] ] = $i;
			}
			
			$this->update_cache( array( 'name' => 'multimod', 'array' => 1, 'deletefirst' => 1 ) );
        }
		
		//-----------------------------------------
		// Get the topic mod thingies
		//-----------------------------------------
		
		if( count( $this->cache['multimod'] ) AND is_array( $this->cache['multimod'] ) )
		{
			foreach( $this->cache['multimod'] as $r )
			{
				if ( $r['mm_forums'] == '*' OR strstr( ",".$r['mm_forums'].",", ",".$fid."," ) )
				{
					$mm_array[] = array( $r['mm_id'], $r['mm_title'] );
				}
			}
		}
		
		return $mm_array;
	}
	
	/*-------------------------------------------------------------------------*/
	// UNPACK MEMBER CACHE
	/*-------------------------------------------------------------------------*/
	/**
	* Unpacks a member's cache
	*
	* Left as a function for any other processing
	*
	* @param	string	Serialized cache array
	* @return	array	Unpacked array
	*/
	function unpack_member_cache( $cache_serialized_array="" )
	{
		return unserialize( $cache_serialized_array );
	}
	
	/*-------------------------------------------------------------------------*/
	// PACK MEMBER CACHE
	/*-------------------------------------------------------------------------*/
	/**
	* Packs up member's cache
	*
	* Takes an existing array and updates member's DB row
	* This will overwrite any existing entries by the same
	* key and create new entries for non-existing rows
	*
	* @param	integer	Member ID
	* @param	array	New array
	* @param	array	Current Array (optional)
	* @return	boolean
	*/
	function pack_and_update_member_cache( $member_id, $new_cache_array, $current_cache_array='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$member_id = intval( $member_id );
		
		//-----------------------------------------
		// Got a member ID?
		//-----------------------------------------
		
		if ( ! $member_id )
		{
			return FALSE;
		}
		
		//-----------------------------------------
		// Got anything to update?
		//-----------------------------------------
		
		if ( ! is_array( $new_cache_array ) OR ! count( $new_cache_array ) )
		{
			return FALSE;
		}
		
		//-----------------------------------------
		// Got a current cache?
		//-----------------------------------------
		
		if ( ! is_array( $current_cache_array ) )
		{
			$member = $this->DB->build_and_exec_query( array( 'select' => "members_cache", 'from' => 'members', 'where' => 'id='.$member_id ) );
			
			$member['members_cache'] = $member['members_cache'] ? $member['members_cache'] : array();
			
			$current_cache_array = @unserialize( $member['members_cache'] );
		}
		
		//-----------------------------------------
		// Overwrite...
		//-----------------------------------------
		
		foreach( $new_cache_array as $k => $v )
		{
			$current_cache_array[ $k ] = $v;
		}
		
		//-----------------------------------------
		// Update...
		//-----------------------------------------
		
		$this->DB->do_update( 'members', array( 'members_cache' => serialize( $current_cache_array ) ), 'id='.$member_id );
		
		//-----------------------------------------
		// Set member array right...
		//-----------------------------------------
		
		$this->member['_cache'] = $current_cache_array;
	}
	
	/*-------------------------------------------------------------------------*/
	// UPDATE FORUM CACHE
	/*-------------------------------------------------------------------------*/
	/**
	* Updates forum cache (loads all, recaches all)
	*
	* @return	void
	*/
	function update_forum_cache()
	{
		$ignore_me = array( 'redirect_url', 'redirect_loc', 'rules_text', 'permission_custom_error', 'notify_modq_emails' );
		
		if ( isset($this->vars['forum_cache_minimum']) AND $this->vars['forum_cache_minimum'] )
		{
			$ignore_me[] = 'description';
			$ignore_me[] = 'rules_title';
		}
		
		$this->cache['forum_cache'] = array();
			
		$this->DB->simple_construct( array( 'select' => '*',
											'from'   => 'forums',
											'order'  => 'parent_id, position'
								   )      );
		$this->DB->simple_exec();
		
		while( $f = $this->DB->fetch_row() )
		{
			$fr = array();
			
			$perms = unserialize(stripslashes($f['permission_array']));
			
			//-----------------------------------------
			// Stuff we don't need...
			//-----------------------------------------
			
			if ( $f['parent_id'] == -1 )
			{
				$fr['id']				    = $f['id'];
				$fr['sub_can_post']         = $f['sub_can_post'];
				$fr['name'] 		        = $f['name'];
				$fr['parent_id']	        = $f['parent_id'];
				$fr['show_perms']	        = $perms['show_perms'];
				$fr['skin_id']		        = $f['skin_id'];
				$fr['permission_showtopic'] = $f['permission_showtopic'];
			}
			else
			{
				foreach( $f as $k => $v )
				{
					if ( in_array( $k, $ignore_me ) )
					{
						continue;
					}
					else
					{
						if ( $v != "" )
						{
							$fr[ $k ] = $v;
						}
					}
				}
				
				$fr['read_perms']   	= isset($perms['read_perms']) 		? $perms['read_perms'] 		: '';
				$fr['reply_perms']  	= isset($perms['reply_perms']) 		? $perms['reply_perms'] 	: '';
				$fr['start_perms']  	= isset($perms['start_perms']) 		? $perms['start_perms'] 	: '';
				$fr['upload_perms'] 	= isset($perms['upload_perms']) 	? $perms['upload_perms'] 	: '';
				$fr['download_perms'] 	= isset($perms['download_perms'])	? $perms['download_perms'] 	: '';
				$fr['show_perms']   	= isset($perms['show_perms']) 		? $perms['show_perms'] 		: '';
				
				unset($fr['permission_array']);
			}
			
			$this->cache['forum_cache'][ $fr['id'] ] = $fr;
		}
		
		$this->update_cache( array( 'name' => 'forum_cache', 'array' => 1, 'deletefirst' => 0, 'donow' => 0 ) );
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// UPDATE CACHE
	//
	/*-------------------------------------------------------------------------*/
	
	/**
	* Updates cache
	*
	* @param	array	Cache values (name, value, deletefirst, donow)
	* @todo		remove some of the MySQL specific code
	* @return	void
	*/
	
	function update_cache( $v=array() )
	{
		//-----------------------------------------
		// Don't cache forums?
		//-----------------------------------------
		
		if ( $v['name'] == 'forum_cache' AND isset($this->vars['no_cache_forums']) AND $this->vars['no_cache_forums'] )
		{ 
			return;
		}
		
		$v['donow'] = isset($v['donow']) ? $v['donow'] : 0;
		
		//-----------------------------------------
		// Next...
		//-----------------------------------------
		
		if ( $v['name'] )
		{
			if ( ! isset($v['value']) OR !$v['value'] )
			{
				if ( isset($v['array']) AND $v['array'] )
				{
					$value = serialize($this->cache[ $v['name'] ]);
				}
				else
				{
					$value = $this->cache[ $v['name'] ];
				}
			}
			else
			{
				if ( isset($v['array']) AND $v['array'] )
				{				
					$value = serialize($v['value']);
				}
				else
				{
					$value = $v['value'];
				}
			}
			
			$this->DB->no_escape_fields['cs_key'] = 1;
			
			if ( $v['deletefirst'] == 1 )
			{
				if ( $v['donow'] )
				{
					$this->DB->do_replace_into( 'cache_store', array( 'cs_array' => intval($v['array']), 'cs_key' => $v['name'], 'cs_value' => $value ), array( 'cs_key' ) );
				}
				else
				{
					$this->DB->do_shutdown_replace_into( 'cache_store', array( 'cs_array' => intval($v['array']), 'cs_key' => $v['name'], 'cs_value' => $value ), array( 'cs_key' ) );
				}
			}
			else
			{
				if ( $v['donow'] )
				{
					$this->DB->do_update( 'cache_store', array( 'cs_array' => intval($v['array']), 'cs_value' => $value ), "cs_key='{$v['name']}'" );
				}
				else
				{
					$this->DB->do_shutdown_update( 'cache_store', array( 'cs_array' => intval($v['array']), 'cs_value' => $value ), "cs_key='{$v['name']}'" );
				}
			}
			
			if( is_object($this->cachelib) )
			{
				if( !$value )
				{
					$value = "EMPTY";
				}
				
				$this->cachelib->do_remove( $v['name'] );
				$this->cachelib->do_put( $v['name'], $value );
			}
		}
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// MY DECONSTRUCTOR
	//
	/*-------------------------------------------------------------------------*/
	
	/**
	* Manual deconstructor
	*
	* Runs any SQL shutdown queries and mail tasks
	*
	* @return	void
	*/
	
	function my_deconstructor()
	{
		//-----------------------------------------
		// Update Server Load
		//-----------------------------------------
				
        if ($this->vars['load_limit'] > 0)
        {
	        $server_load_found = 0;
	        
	        //-----------------------------------------
	        // Check cache first...
	        //-----------------------------------------
	        
	        if ( $this->cache['systemvars']['loadlimit'] )
	        {
		        $loadinfo = explode( "-", $this->cache['systemvars']['loadlimit'] );
		        
		        if ( intval($loadinfo[1]) > (time() - 10) )
		        {
			        //-----------------------------------------
			        // Cache is less than 1 minute old
			        //-----------------------------------------
			        
			        $server_load_found = 1;
    			}
			}
			
	        //-----------------------------------------
	        // No cache or it's old, check real time
	        //-----------------------------------------
			
			if ( ! $server_load_found )
			{
		        # @ supressor fixes warning in >4.3.2 with open_basedir restrictions
		        
	        	if ( @file_exists('/proc/loadavg') )
	        	{
	        		if ( $fh = @fopen( '/proc/loadavg', 'r' ) )
	        		{
	        			$data = @fread( $fh, 6 );
	        			@fclose( $fh );
	        			
	        			$load_avg = explode( " ", $data );
	        			
	        			$this->server_load = trim($load_avg[0]);
	        		}
	        	}
	        	else if( strstr( strtolower(PHP_OS), 'win' ) )
	        	{
			        /*---------------------------------------------------------------
			        | typeperf is an exe program that is included with Win NT,
			        |	XP Pro, and 2K3 Server.  It can be installed on 2K from the
			        |	2K Resource kit.  It will return the real time processor
			        |	Percentage, but will take 1 second processing time to do so.
			        |	This is why we shall cache it, and check every 10 secs.
			        |
			        |	Can also be obtained from COM, but it's extremely slow...
			        ---------------------------------------------------------------*/
		        	
		        	$serverstats = @shell_exec("typeperf \"Processor(_Total)\% Processor Time\" -sc 1");
		        	
		        	if( $serverstats )
		        	{
						$server_reply = explode( "\n", str_replace( "\r", "", $serverstats ) );
						$serverstats = array_slice( $server_reply, 2, 1 );
						
						$statline = explode( ",", str_replace( '"', '', $serverstats[0] ) );
						
						$this->server_load = round( $statline[1], 2 );
					}
				}
	        	else
	        	{
					if ( $serverstats = @exec("uptime") )
					{
						preg_match( "/(?:averages)?\: ([0-9\.]+)[^0-9\.]+([0-9\.]+)[^0-9\.]+([0-9\.]+)\s*/", $serverstats, $load );
						
						$this->server_load = $load[1];
					}
				}
				
				if ( $this->server_load )
				{
					$this->cache['systemvars']['loadlimit'] = $this->server_load."-".time();
					
					$this->update_cache( array( 'array' => 1, 'name' => 'systemvars', 'donow' => 1, 'deletefirst' => 0 ) );
				}
			}
		}
		
		//-----------------------------------------
		// Process mail queue
		//-----------------------------------------
			
		$this->process_mail_queue();		
		
		//-----------------------------------------
		// Any shutdown queries
		//-----------------------------------------
		
		$this->DB->return_die = 0;
		
		if ( count( $this->DB->obj['shutdown_queries'] ) )
		{
			foreach( $this->DB->obj['shutdown_queries'] as $q )
			{
				$this->DB->query( $q );
			}
		}
		
		$this->DB->return_die = 1;
		
		$this->DB->obj['shutdown_queries'] = array();
		
		$this->DB->close_db();
		
		if( is_object($this->cachelib) )
		{
			// memcache, primarily, though disconnect will
			// happen automatically just like DB anyways
			
			$this->cachelib->disconnect();
		}		
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// Process Mail Queue
	//
	/*-------------------------------------------------------------------------*/
	
	/**
	* Process mail queue
	*
	* @return	void
	*/
	
	function process_mail_queue()
	{
		//-----------------------------------------
		// SET UP
		//-----------------------------------------
		
		$this->vars['mail_queue_per_blob']       = isset($this->vars['mail_queue_per_blob']) ? $this->vars['mail_queue_per_blob'] : 5;
		
		$this->cache['systemvars']['mail_queue'] = isset( $this->cache['systemvars']['mail_queue'] ) ? intval( $this->cache['systemvars']['mail_queue'] ) : 0;
		
		$sent_ids = array();
		
		if ( $this->cache['systemvars']['mail_queue'] > 0 )
		{
			//-----------------------------------------
			// Require the emailer...
			//-----------------------------------------
			
			require_once( ROOT_PATH . 'sources/classes/class_email.php' );
			$emailer = new emailer(ROOT_PATH);
			$emailer->ipsclass =& $this;
			$emailer->email_init();
			
			//-----------------------------------------
			// Get the mail stuck in the queue
			//-----------------------------------------
			
			$this->DB->simple_construct( array( 'select' => '*', 'from' => 'mail_queue', 'order' => 'mail_id', 'limit' => array( 0, $this->vars['mail_queue_per_blob'] ) ) );
			$this->DB->simple_exec();
			
			while ( $r = $this->DB->fetch_row() )
			{
				$data[]     = $r;
				$sent_ids[] = $r['mail_id'];
			}
			
			if ( count($sent_ids) )
			{
				//-----------------------------------------
				// Delete sent mails and update count
				//-----------------------------------------
				
				$this->cache['systemvars']['mail_queue'] = $this->cache['systemvars']['mail_queue'] - count($sent_ids);
				
				$this->DB->simple_exec_query( array( 'delete' => 'mail_queue', 'where' => 'mail_id IN ('.implode(",", $sent_ids).')' ) );
			
				foreach( $data as $mail )
				{
					if ( $mail['mail_to'] and $mail['mail_subject'] and $mail['mail_content'] )
					{
						$emailer->to      = $mail['mail_to'];
						$emailer->from    = $mail['mail_from'] ? $mail['mail_from'] : $this->vars['email_out'];
						$emailer->subject = $mail['mail_subject'];
						$emailer->message = $mail['mail_content'];
						
						if ( $mail['mail_html_on'] )
						{
							$emailer->html_email = 1;
						}
						else
						{
							$emailer->html_email = 0;
						}
						
						$emailer->send_mail();
					}
				}
			}
			else
			{
				//-----------------------------------------
				// No mail after all?
				//-----------------------------------------
				
				$this->cache['systemvars']['mail_queue'] = 0;
			}
			
			//-----------------------------------------
			// Update cache with remaning email count
			//-----------------------------------------
			
			$this->update_cache( array( 'array' => 1, 'name' => 'systemvars', 'donow' => 1, 'deletefirst' => 0 ) );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Load a ACP template file
	/*-------------------------------------------------------------------------*/
	
	/**
	* Load an ACP skin template file for use
	*
	* @return	object	Class object
	*/
	
	function acp_load_template( $template )
	{
		if ( ! $this->skin_acp )
		{
			$this->skin_acp = 'IPB2_Standard';
		}
		
		require_once( ROOT_PATH."skin_acp/".$this->skin_acp."/acp_skin_html/".$template.".php" );
		$tmp           = new $template();
		$tmp->ipsclass =& $this;
		return $tmp;
	}
	
	/*-------------------------------------------------------------------------*/
    // Require, parse and return an array containing the language stuff                 
    /*-------------------------------------------------------------------------*/ 
    
    /**
	* Load an ACP language file. Populates $this->lang
	*
	* @param	string	File name
	* @return	void
	* @since	2.1
	*/
    function acp_load_language( $file="" )
    {
    	if ( ! $this->lang_id )
    	{
    		$this->lang_id = $this->member['language'] ? $this->member['language'] : $this->vars['default_language'];

			if ( ($this->lang_id != $this->vars['default_language']) and ( ! is_dir( ROOT_PATH."cache/lang_cache/".$this->lang_id ) ) )
			{
				$this->lang_id = $this->vars['default_language'];
			}
			
			//-----------------------------------------
			// Still nothing?
			//-----------------------------------------
			
			if ( ! $this->lang_id )
			{
				$this->lang_id = 'en';
			}
		}
    	
    	//-----------------------------------------
    	// Load it
    	//-----------------------------------------

    	if( file_exists( ROOT_PATH."cache/lang_cache/".$this->lang_id."/".$file.".php" ) )
    	{
	        require_once( ROOT_PATH."cache/lang_cache/".$this->lang_id."/".$file.".php" );
	        
	        if ( is_array( $lang ) )
	        {
				foreach ($lang as $k => $v)
				{
					$this->acp_lang[ $k ] = $v;
				}
	        }
        }
        
        unset($lang);
    }
	
	/*-------------------------------------------------------------------------*/
	//
	// Load a template file from DB or from PHP file
	//
	/*-------------------------------------------------------------------------*/
	
	/**
	* Load a normal template file from either cached PHP file or
	* from the DB. Populates $this->compiled_templates[ _template_name_ ]
	*
	* @param	string	Template name
	* @param	integer	Template set ID
	* @return	void
	*/
	
	function load_template( $name, $id='' )
	{
		$tags 	= 1;
		$loaded	= 0;
		
		//-----------------------------------------
		// Select ID
		//-----------------------------------------
		
		if ( ! $id )
		{
			$id = $this->skin['_skincacheid'];
		}
	
		//-----------------------------------------
		// Full name
		//-----------------------------------------
		
		$full_name        = $name.'_'.intval($id);
		$skin_global_name = 'skin_global_'.$id;
		$_name            = $name;
		
		//-----------------------------------------
		// Already got this template loaded?
		//-----------------------------------------
		
		if ( in_array( $full_name, $this->loaded_templates ) )
		{
			return;
		}
	
		//-----------------------------------------
		// Not running safemode skins?
		//-----------------------------------------
		
		if ( $this->vars['safe_mode_skins'] == 0 AND $this->vars['safe_mode'] == 0 )
		{
			//-----------------------------------------
			// Simply require and return
			//-----------------------------------------
			
			if ( $name != 'skin_global')
			{ 
				if ( ! in_array( $skin_global_name, $this->loaded_templates ) )
				{
					//-----------------------------------------
					// Suck in skin global..
					//-----------------------------------------
					
					if( $this->load_template_from_php( 'skin_global', 'skin_global_'.$id, $id ) )
					{
						$loaded = 1;
					}
					
					//-----------------------------------------
					// Suck in normal file...
					//-----------------------------------------
					
					if( !$this->load_template_from_php( $_name, $name.'_'.$id, $id ) )
					{
						$loaded = 0;
					}
				}
				else
				{
					//-----------------------------------------
					// Suck in normal file...
					//-----------------------------------------
					
					if( $this->load_template_from_php( $_name, $name.'_'.$id, $id ) )
					{
						$loaded = 1;
					}
				}
			}
			else
			{
				if ( $name == 'skin_global' )
				{
					//-----------------------------------------
					// Suck in skin global..
					//-----------------------------------------
					
					if( $this->load_template_from_php( 'skin_global', 'skin_global_'.$id, $id ) )
					{
						$loaded = 1;
					}
					
					return;
				}
				else
				{
					//-----------------------------------------
					// Suck in normal file...
					//-----------------------------------------
					
					if( $this->load_template_from_php( $_name, $name.'_'.$id, $id ) )
					{
						$loaded = 1;
					}
				}
			}
		}
		
		//-----------------------------------------
		// safe_mode_skins OR flat file load failed
		//-----------------------------------------
		
		if( !$loaded )
		{
			//-----------------------------------------
			// We're using safe mode skins, yippee
			// Load the data from the DB
			//-----------------------------------------
			
			$skin_global = "";
			$other_skin  = "";
			$this->skin['_type'] = 'Database Skins';
				
			if ( $this->loaded_templates[ $skin_global_name ] == "" and $name != 'skin_global')
			{
				//-----------------------------------------
				// Skin global not loaded...
				//-----------------------------------------
				
				$this->DB->simple_construct( array( 'select' => '*',
													'from'   => 'skin_templates_cache',
													'where'  => "template_set_id=".$id." AND template_group_name IN ('skin_global', '$name')"
										   )      );
									 
				$this->DB->simple_exec();
				
				while ( $r = $this->DB->fetch_row() )
				{
					if ( $r['template_group_name'] == 'skin_global' )
					{
						$skin_global = $r['template_group_content'];
					}
					else
					{
						$other_skin  = $r['template_group_content'];
					}
				}
				
				if ( IN_DEV AND $id == 1 )
				{
					//-----------------------------------------
					// Get template class
					//-----------------------------------------
				
					if ( ! is_object( $this->work_classes['class_template_engine'] ) )
					{
						require_once( KERNEL_PATH . 'class_template_engine.php' );
		
						$this->work_classes['class_template_engine'] = new class_template();
					}
		
					if( $skin_global )
					{
						$skin_global = $this->work_classes['class_template_engine']->convert_cache_to_eval( $skin_global, 'skin_global_1' );
					}
					
					if( $other_skin )
					{
						$other_skin = $this->work_classes['class_template_engine']->convert_cache_to_eval( $other_skin, $name.'_'.$id );
					}
				}
				
				//print $other_skin;exit;

				eval($skin_global);
				
				$this->compiled_templates['skin_global']           =  new $skin_global_name();
				$this->compiled_templates['skin_global']->ipsclass =& $this;
				
				# Add to loaded templates
				$this->loaded_templates[ $skin_global_name ] = $skin_global_name;
			}
			else
			{
				//-----------------------------------------
				// Skin global is loaded..
				//-----------------------------------------
				
				if ( $name == 'skin_global' and in_array( $skin_global_name, $this->loaded_templates ) )
				{
					return;
				}
				
				//-----------------------------------------
				// Load the skin, man
				//-----------------------------------------
				
				$this->DB->simple_construct( array( 'select' => '*',
													'from'   => 'skin_templates_cache',
													'where'  => "template_set_id=".$id." AND template_group_name='$name'"
										   )      );
									 
				$this->DB->simple_exec();
				
				$r = $this->DB->fetch_row();
				
				$other_skin  = $r['template_group_content'];
				
				if ( IN_DEV AND $id == 1 )
				{
					//-----------------------------------------
					// Get template class
					//-----------------------------------------
				
					if ( ! is_object( $this->work_classes['class_template_engine'] ) )
					{
						require_once( KERNEL_PATH . 'class_template_engine.php' );
		
						$this->work_classes['class_template_engine'] = new class_template();
					}
		
					if( $other_skin )
					{
						$other_skin = $this->work_classes['class_template_engine']->convert_cache_to_eval( $other_skin, $name.'_'.$id );
					}
				}				
			}
			
			eval($other_skin);
			
			if ( $name == 'skin_global' )
			{
				$this->compiled_templates['skin_global']           =  new $skin_global_name();
				$this->compiled_templates['skin_global']->ipsclass =& $this;
				
				# Add to loaded templates
				$this->loaded_templates[ $skin_global_name ] = $skin_global_name;
			}
			else
			{
				$this->compiled_templates[ $name ]           =  new $full_name();
				$this->compiled_templates[ $name ]->ipsclass =& $this;
				
				# Add to loaded templates
				$this->loaded_templates[ $full_name ] = $full_name;
			}
		}
		
		//-----------------------------------------
		// LEGACY
		//-----------------------------------------
		
		if ( defined('LEGACY_MODE') && LEGACY_MODE == 1 )
		{
			if ( $name )
			{
				return $this->compiled_templates[ $name ];
			}
		}
	}
	
	/*-------------------------------------------------------------------------*/
    // Load the template bit from the PHP file              
    /*-------------------------------------------------------------------------*/
    
    /**
	* Load the template bit from the PHP file      
	*
	* @var		string	Name of the PHP file (sans .php)
	* @var		string	Name of the class
	* @return	boolean
	*/
	
	function load_template_from_php( $name='skin_global', $full_name='skin_global_1', $id='1' )
	{
		$_NOW = $this->memory_debug_make_flag();
		
		//-----------------------------------------
		// File exist?
		//-----------------------------------------
		
		if( !file_exists( CACHE_PATH."cache/skin_cache/cacheid_".$id."/".$name.".php" ) )
		{
			return FALSE;
		}
		
		//-----------------------------------------
		// IN_DEV?
		//-----------------------------------------
		
		if ( IN_DEV AND $id == 1 )
		{
			//-----------------------------------------
			// Get data...
			//-----------------------------------------
		
			$data = implode( '', file( CACHE_PATH."cache/skin_cache/cacheid_".$id."/".$name.".php" ) );
		
			//-----------------------------------------
			// Get template class
			//-----------------------------------------
		
			if ( ! is_object( $this->work_classes['class_template_engine'] ) )
			{
				require_once( KERNEL_PATH . 'class_template_engine.php' );

				$this->work_classes['class_template_engine'] = new class_template();
			}
			
			$toeval = $this->work_classes['class_template_engine']->convert_cache_to_eval( $data, $full_name );
			
			#if ( $name == 'skin_topic' ) { print $toeval; exit(); }
			
			eval( $toeval );
		}
		else
		{
			require_once( CACHE_PATH."cache/skin_cache/cacheid_".$id."/".$name.".php" );
		}
		
		$this->compiled_templates[ $name ]           =  new $full_name();
		$this->compiled_templates[ $name ]->ipsclass =& $this;
	
		# Add to loaded templates
		$this->loaded_templates[ $full_name ] = $full_name;
	
		$this->memory_debug_add( "IPSCLASS: Loaded skin file - $name", $_NOW );
		
		return TRUE;
	}
	
	/*-------------------------------------------------------------------------*/
    // SKIN, sort out the skin stuff                 
    /*-------------------------------------------------------------------------*/
    
    /**
	* Load a skin, macro, settings, etc
	*
	* @return	array	Database row (not really used anymore)
	*/
	
    function load_skin()
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	$id                    = -1;
    	$skin_set              = 0;
    	$from_forum            = 0;
    	$this->input['skinid'] = isset($this->input['skinid']) ? intval($this->input['skinid']) : 0;
    	$this->member['skin']  = isset($this->member['skin'])  ? intval($this->member['skin'])  : 0;
    	
		//-----------------------------------------
		// Skin ID 1 is reserved..	
		//-----------------------------------------
		
		if ( $this->input['skinid'] == 1 )
		{
			$this->input['skinid'] = 0;
		}
		
    	//-----------------------------------------
    	// Do we have a cache?
    	//-----------------------------------------
    	
    	if ( ! is_array( $this->cache['skin_id_cache'] ) OR ! count( $this->cache['skin_id_cache'] ) )
    	{
    		require_once( ROOT_PATH.'sources/lib/admin_cache_functions.php' );
    		$admin           = new admin_cache_functions();
    		$admin->ipsclass =& $this;
    		
    		$this->cache['skin_id_cache'] = $admin->_rebuild_skin_id_cache();
       	}
    	
    	//-----------------------------------------
    	// Search bot?
    	//-----------------------------------------
    	
    	if ( ( $this->is_bot == 1 ) and ( $this->vars['spider_suit'] != "" ) )
    	{
    		$skin_set = 1;
    		$id       = $this->vars['spider_suit'];
    	}
    	else
    	{
			//-----------------------------------------
			// URL remapping?
			//-----------------------------------------
			
			if ( isset($this->cache['skin_remap']) and is_array( $this->cache['skin_remap'] ) AND count( $this->cache['skin_remap'] ) )
			{
				foreach( $this->cache['skin_remap'] as $id => $data )
				{
					if ( $data['map_match_type'] == 'exactly' )
					{
						if ( strtolower( $data['map_url'] ) == strtolower( $this->query_string_real ) )
						{
							$skin_set = 1;
							$id       = $data['map_skin_set_id'];
							break;
						}
					}
					else if ( $data['map_match_type'] == 'contains' )
					{
						if ( stristr( $this->query_string_real, $data['map_url'] ) )
						{ 
							$skin_set = 1;
							$id       = $data['map_skin_set_id'];
							break;
						}
					}
				}
			}
			
			//-----------------------------------------
			// Still not set?
			//-----------------------------------------
			
			if ( ! $skin_set )
			{
				//-----------------------------------------
				// Do we have a skin for a particular forum?
				//-----------------------------------------
			
				if ( (isset($this->input['f']) AND $this->input['f']) AND (isset($this->input['act']) AND $this->input['act'] != 'UserCP') )
				{
					if ( isset($this->cache['forum_cache'][ $this->input['f'] ]['skin_id']) AND $this->cache['forum_cache'][ $this->input['f'] ]['skin_id'] > 0 )
					{
						$id         = $this->cache['forum_cache'][ $this->input['f'] ]['skin_id'];
						$skin_set   = 1;
						$from_forum = 1;
					}
				}
			
				//-----------------------------------------
				// Are we allowing user chooseable skins?
				//-----------------------------------------
			
				if ( $skin_set != 1 AND $this->vars['allow_skins'] == 1 )
				{
					if ( $this->input['skinid'] )
					{
						$id        = $this->input['skinid'];
						$skin_set  = 1;
					}
					else if ( $this->member['skin'] )
					{
						$id       = $this->member['skin'];
						$skin_set = 1;
					}
				}
			}
    	}
    	
    	//-----------------------------------------
		// Nothing set / hidden and not admin? Choose the default
		//-----------------------------------------
		
		if ( isset($this->cache['skin_id_cache'][ $id ]['set_hidden']) AND $this->cache['skin_id_cache'][ $id ]['set_hidden'] )
		{
			if ( $from_forum )
			{
				$skin_set = 1;
			}
			else if ( $this->member['g_access_cp'] )
			{
				$skin_set = 1;
			}
			else if ( $this->is_bot )
			{
				$skin_set = 1;
			}
			else
			{
				$skin_set = 0;
			}
		}
			
		if ( ! $id OR ! $skin_set OR ! is_array($this->cache['skin_id_cache'][ $id ]) )
		{
			foreach( $this->cache['skin_id_cache'] as $data )
			{
				if ( $data['set_default'] )
				{
					$id       = $data['set_skin_set_id'];
					$skin_set = 1;
				}
			}
		}
		
        //------------------------------------------
        // Still no skin? - no default skin set
        //------------------------------------------
        
        if( ! $id OR ! $skin_set )
        {
            $skin_data = reset($this->cache['skin_id_cache']);
            $id        = $skin_data['skin_set_id'];
            $skin_set  = 1;
        }		
		
		//-----------------------------------------
		// Get the skin
		//-----------------------------------------
    	
		$db_skin = array();
		
		if ( is_object( $this->cachelib ) )
		{
			$db_skin = $this->cachelib->do_get( 'Skin_Store_' . $id );
		}
		
		if ( ! is_array($db_skin) OR ! count($db_skin) )
		{
			$db_skin = $this->DB->simple_exec_query( array( 'select' => 'set_cache_css,set_cache_wrapper,set_cache_macro,set_image_dir,set_emoticon_folder,set_skin_set_id,set_name,set_css_method', 'from' => 'skin_sets', 'where' => 'set_skin_set_id='.$id ) );
		
			if( is_object($this->cachelib) )
			{
				$this->cachelib->do_put( 'Skin_Store_' . $id, $db_skin, 86400 );
			}
		}		
		
		$this->skin['_css']         = $db_skin['set_cache_css'];
		$this->skin['_wrapper']     = $db_skin['set_cache_wrapper'];
		$this->skin['_macro']       = $db_skin['set_cache_macro'];
		$this->skin['_imagedir']    = $db_skin['set_image_dir'];
		$this->skin['_emodir']      = $db_skin['set_emoticon_folder'];
		$this->skin['_setid']       = $db_skin['set_skin_set_id'];
    	$this->skin['_setname']     = $db_skin['set_name'];
    	$this->skin['_usecsscache'] = $db_skin['set_css_method'] ? 1 : 0;
    	$this->skin['_macros']      = unserialize(stripslashes($this->skin['_macro']));
    	$this->skin['_skincacheid'] = $db_skin['set_skin_set_id'];
		$this->skin['_csscacheid']  = $db_skin['set_skin_set_id'];
			
    	if ( IN_DEV )
    	{
    		$this->skin['_skincacheid'] = file_exists( CACHE_PATH.'cache/skin_cache/cacheid_1' ) ? 1 : $db_skin['set_skin_set_id'];
			$this->skin['_usecsscache'] = file_exists( CACHE_PATH.'style_images/css_'. $this->skin['_csscacheid'] .'.css' ) ? 1 : 0;
			$this->skin['_csscacheid']  = 1;
    	}
   
    	//-----------------------------------------
    	// Setting the skin?
    	//-----------------------------------------
    	
    	if ( isset($this->input['setskin']) AND $this->input['setskin'] AND $this->member['id'] )
    	{
    		$this->DB->simple_construct( array( 'update' => 'members',
												'set'    => "skin=".intval($id),
												'where'  => "id=".$this->member['id']
									   )      );
			$this->DB->simple_exec();
    		
    		$this->member['skin'] = $id;
    	}
    	
    	unset($db_skin);
    	
    	return;
    }
	
	/*-------------------------------------------------------------------------*/
    // Require, parse and return an array containing the language stuff                 
    /*-------------------------------------------------------------------------*/ 
    
    /**
	* Load a language file. Populates $this->lang
	*
	* @param	string	File name
	* @return	void
	* @since	2.1
	*/
    function load_language( $file="" )
    {
		//-----------------------------------------
		// Memory
		//-----------------------------------------
		
		$_NOW = $this->memory_debug_make_flag();
		
	    $this->vars['default_language'] = isset($this->vars['default_language']) ? $this->vars['default_language'] : 'en';
	    
    	if ( ! $this->lang_id )
    	{
    		$this->lang_id = isset($this->member['language']) ? $this->member['language'] : $this->vars['default_language'];

			if ( ($this->lang_id != $this->vars['default_language']) and ( ! is_dir( ROOT_PATH."cache/lang_cache/".$this->lang_id ) ) )
			{
				$this->lang_id = $this->vars['default_language'];
			}
			
			//-----------------------------------------
			// Still nothing?
			//-----------------------------------------
			
			if ( ! $this->lang_id )
			{
				$this->lang_id = 'en';
			}
		}
    	
    	//-----------------------------------------
    	// Load it
    	//-----------------------------------------
		
		if ( file_exists( ROOT_PATH."cache/lang_cache/".$this->lang_id."/".$file.".php" ) )
		{
        	require ( ROOT_PATH."cache/lang_cache/".$this->lang_id."/".$file.".php" );
        
	        if ( isset($lang) AND is_array( $lang ) )
	        {
				foreach ($lang as $k => $v)
				{
					$this->lang[ $k ] = $v;
				}
	        }
	    }
		
		//-----------------------------------------
		// Memory
		//-----------------------------------------
		
		$this->memory_debug_add( "IPSCLASS: Loaded language file - $file", $_NOW );
		
		//-----------------------------------------
		// Clean up
		//-----------------------------------------
		
		unset( $lang, $file );
    }
	
	/*-------------------------------------------------------------------------*/
	// Get new PM notification window
	/*-------------------------------------------------------------------------*/
	
	/**
	* Get a new PM notification message
	*
	* @param	integer	Current offset
	* @param	integer	For Ajax flag
	* @return	string	Parsed Message
	* @todo		Remove deprecated post_parser.php
	* @since	2.0
	*/
	function get_new_pm_notification( $limit=0, $xmlout = 0 )
	{
		//-----------------------------------------
		// Make sure we have a skin...
		//-----------------------------------------
		
		if ( ! $this->compiled_templates['skin_global'] )
		{
			$this->load_template( 'skin_global' );
		}
		
		//-----------------------------------------
		// posty parsery
		//-----------------------------------------
		
		require_once( ROOT_PATH."sources/handlers/han_parse_bbcode.php" );
        $parser                      =  new parse_bbcode();
        $parser->ipsclass            =& $this;
        $parser->allow_update_caches =  0;
        $parser->parse_bbcode		 = 1;
        $parser->parse_smilies		 = 1;
        $parser->parse_html		 	 = 0;
		
		//-----------------------------------------
		// Get last PM details
		//-----------------------------------------
		
		$this->DB->cache_add_query( 'msg_get_new_pm_notification', array( 'mid' => $this->member['id'], 'limit_a' => intval($limit) ) );
		$this->DB->simple_exec();
		
		$msg = $this->DB->fetch_row();
		
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( ! $msg['msg_id'] and ! $msg['mt_id'] and ! $msg['id'] )
		{
			return '<!-- CANT FIND MESSAGE -->';
		}
		
		//-----------------------------------------
		// Strip and wrap
		//-----------------------------------------
		
		$msg['msg_post'] = $parser->pre_edit_parse( $msg['msg_post'] );

		$msg['msg_post'] = $parser->strip_all_tags( $msg['msg_post'], false );
		
		$msg['msg_post'] = preg_replace("#([^\s<>'\"/\.\\-\?&\n\r\%]{80})#i", " \\1"."<br />", $msg['msg_post']);
		$msg['msg_post'] = str_replace( "\n", "<br />", trim($msg['msg_post']) );
		
		if ( strlen( $msg['msg_post'] ) > 300 )
		{
			$msg['msg_post'] = substr( $msg['msg_post'], 0, 350 ) . '...';
			$msg['msg_post'] = preg_replace( "/&(#(\d+;?)?)?\.\.\.$/", '...', $msg['msg_post'] );
		}
		
		//-----------------------------------------
		// Add attach icon
		//-----------------------------------------
		
		if ( $msg['mt_hasattach'] )
		{
			$msg['attach_img'] = '<{ATTACH_ICON}>&nbsp;';
		}
		
		//-----------------------------------------
		// Date
		//-----------------------------------------
		
		$msg['msg_date'] = $this->get_date( $msg['msg_date'], 'TINY' );
		
		//-----------------------------------------
		// Next / Total links
		//-----------------------------------------
		
		$msg['_cur_num']   = intval($limit) + 1;
		$msg['_msg_total'] = intval($this->member['new_msg']) ? intval($this->member['new_msg']) : 1;
		
		//-----------------------------------------
		// Return loverly HTML
		//-----------------------------------------
		
		$return = $this->compiled_templates['skin_global']->msg_get_new_pm_notification( $msg, $xmlout );
		
		//-----------------------------------------
		// XML request?
		//-----------------------------------------
		
		return $return;
	}
	
	/*-------------------------------------------------------------------------*/
	// Content search hightlight
	/*-------------------------------------------------------------------------*/
	
	/**
	* Replaces text with highlighted blocks
	*
	* @param	string	Incoming Content
	* @param	string	HL attribute
	* @return	string	Formatted text 
	* @since	2.2.0
	*/
	
	function content_search_highlight( $text, $highlight )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$highlight  = $this->parse_clean_value( urldecode( $highlight ) );
		$loosematch = strstr( $highlight, '*' ) ? 1 : 0;
		$keywords   = str_replace( '*', '', str_replace( "+", " ", str_replace( "++", "+", str_replace( '-', '', trim($highlight) ) ) ) );
		$word_array = array();
		$endmatch   = "(.)?";
		$beginmatch = "(.)?";
		
		//-----------------------------------------
		// Go!
		//-----------------------------------------
		
		if ( $keywords )
		{
			if ( preg_match("/,(and|or),/i", $keywords) )
			{
				while ( preg_match("/,(and|or),/i", $keywords, $match) )
				{
					$word_array = explode( ",".$match[1].",", $keywords );
					$keywords   = str_replace( $match[0], '' ,$keywords );
				}
			}
			else if ( strstr( $keywords, ' ' ) )
			{
				$word_array = explode( ' ', str_replace( '  ', ' ', $keywords ) );
			}
			else
			{
				$word_array[] = $keywords;
			}
			
			if ( ! $loosematch )
			{
				$beginmatch = "(^|\s|\>|;)";
				$endmatch   = "(\s|,|\.|!|<br|&|$)";
			}
	
			if ( is_array($word_array) )
			{
				foreach ( $word_array as $keywords )
				{
					preg_match_all( "/{$beginmatch}(".preg_quote($keywords, '/')."){$endmatch}/is", $text, $matches );
					
					for ( $i = 0; $i < count($matches[0]); $i++ )
					{
						$text = str_replace( $matches[0][$i], $matches[1][$i]."<span class='searchlite'>".$matches[2][$i]."</span>".$matches[3][$i], $text );
					}
				}
			}
		}
		
		return $text;
	}
	
	/*-------------------------------------------------------------------------*/
	// XSS Clean: URLs
	/*-------------------------------------------------------------------------*/
	
	/**
	* Check a URL to make sure it's not all hacky
	*
	* @param	string	Input String
	* @return	boolean 
	* @since	2.1.0
	*/
	
	function xss_check_url( $url )
	{
		$url = trim( urldecode( $url ) );
		
		if ( ! preg_match( "#^https?://(?:[^<>*\"]+|[a-z0-9/\._\- !]+)$#iU", $url ) )
		{
			return FALSE;
		}
		
		return TRUE;
	}
	
	/*-------------------------------------------------------------------------*/
	// XSS Clean: Nasty HTML
	/*-------------------------------------------------------------------------*/
	
	/**
	* Remove script tags from HTML (well, best shot anyway)
	*
	* @param	string	Input HTML
	* @return	string  Cleaned HTML 
	* @since	2.1.0
	*/
	
	function xss_html_clean( $html )
	{
		//-----------------------------------------
		// Opening script tags...
		// Check for spaces and new lines...
		//-----------------------------------------
		
		$html = preg_replace( "#<(\s+?)?s(\s+?)?c(\s+?)?r(\s+?)?i(\s+?)?p(\s+?)?t#is"        , "&lt;script" , $html );
		$html = preg_replace( "#<(\s+?)?/(\s+?)?s(\s+?)?c(\s+?)?r(\s+?)?i(\s+?)?p(\s+?)?t#is", "&lt;/script", $html );
		
		//-----------------------------------------
		// Basics...
		//-----------------------------------------
		
		$html = preg_replace( "/javascript/i" , "j&#097;v&#097;script", $html );
		$html = preg_replace( "/alert/i"      , "&#097;lert"          , $html );
		$html = preg_replace( "/about:/i"     , "&#097;bout:"         , $html );
		$html = preg_replace( "/onmouseover/i", "&#111;nmouseover"    , $html );
		$html = preg_replace( "/onclick/i"    , "&#111;nclick"        , $html );
		$html = preg_replace( "/onload/i"     , "&#111;nload"         , $html );
		$html = preg_replace( "/onsubmit/i"   , "&#111;nsubmit"       , $html );
		$html = preg_replace( "/<body/i"      , "&lt;body"            , $html );
		$html = preg_replace( "/<html/i"      , "&lt;html"            , $html );
		$html = preg_replace( "/document\./i" , "&#100;ocument."      , $html );
		
		return $html;
	}
	
	/*-------------------------------------------------------------------------*/
	// HAX Check executable code
	/*-------------------------------------------------------------------------*/
	
	/**
	* Checks for executable code
	*
	* @param	string	Input String
	* @return	boolean 
	* @since	2.2.0
	*/
	
	function hax_check_for_executable_code( $text='' )
	{
		//-----------------------------------------
		// Test
		//-----------------------------------------
		
		if ( preg_match( "#include|require|include_once|require_once|exec|system|passthru|`#si", $text ) )
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	/*-------------------------------------------------------------------------*/
	// Convert string between char-sets
	/*-------------------------------------------------------------------------*/
	
	/**
	* Convert a string between charsets
	*
	* @param	string	Input String
	* @param	string	Current char set
	* @param	string	Destination char set
	* @return	string	Parsed string
	* @since	2.1.0
	*/
	function txt_convert_charsets($t, $original_cset, $destination_cset="")
	{
		$original_cset = strtolower($original_cset);
		$text         = $t;
		
		//-----------------------------------------
		// Did we pass a destination?
		//-----------------------------------------
		
		$destination_cset = strtolower($destination_cset) ? strtolower($destination_cset) : strtolower($this->vars['gb_char_set']);
		
		//-----------------------------------------
		// Not the same?
		//-----------------------------------------
		
		if ( $destination_cset == $original_cset )
		{
			return $t;
		}
		
		//-----------------------------------------
		// Do the convert
		//-----------------------------------------
		
		/*if ( function_exists( 'mb_convert_encoding' ) )
		{
			$text = mb_convert_encoding( $text, $destination_cset, $original_cset );
		}
		else if ( function_exists( 'recode_string' ) )
		{
			$text = recode_string( $original_cset.'..'.$destination_cset, $text );
		}
		else if ( function_exists( 'iconv' ) )
		{
			$text = iconv( $original_cset, $destination_cset.'//TRANSLIT', $text);
		}*/
		
		if ( ! is_object( $this->class_convert_charset ) )
		{
			require_once( KERNEL_PATH.'/class_convert_charset.php' );
			$this->class_convert_charset = new class_convert_charset();
		}
		
		$text = $this->class_convert_charset->convert_charset( $text, $original_cset, $destination_cset );
		
		return $text ? $text : $t;
	}
	
	/*-------------------------------------------------------------------------*/
	// Wordwrap text string (multi-byte safe)
	/*-------------------------------------------------------------------------*/
	
	/**
	* Wordwrap a string at a given length (multi-byte character safe)
	*
	* @param	string	Input String
	* @param	integer	Desired break point
	* @param	string	Break point string
	* @return	string	Parsed string
	* @since	2.2
	*/	
	
	function txt_wordwrap( $text, $breakpoint=80, $char='<br />' )
	{
		$breakpoint = intval($breakpoint) > 0 ? intval($breakpoint) : 80;
		
		if( $this->vars['multibyte_wordwrap'] )
		{
			$str_len = $this->txt_mb_strlen( $text );
			
			if( $str_len < $breakpoint )
			{
				return $text;
			}
			
			$str 		= "";
			$str_arr	= array();
			
			for( $i=0; $i<strlen($text); $i++ )
			{
				if( ord( substr( $text, $i, 1 ) ) > 128 AND ord( substr( $text, $i, 1 ) ) < 256 ) 
				{
					$str_arr[] = substr( $text, $i, 2 );
					$i += 1;
				}
				else if( ord( substr( $text, $i, 1 ) ) > 256 )
				{
					$str_arr[] = substr( $text, $i, 3 );
					$i += 2;
				}
				else
				{
					$str_arr[] = substr( $text, $i, 1 );
				}
			}
			
			$tmp = array_chunk( $str_arr, $breakpoint );
	
			foreach( $tmp as $key => $val ) 
			{
				if( preg_match( "/\s+/", implode( "", $val ) ) )
				{
					$str .= implode( "", $val );
				}
				else
				{
					if( preg_match( "/&[a-zA-Z0-9]$/", implode( "", $val ) ) )
					{
						$str .= preg_replace( "/&[a-zA-Z0-9]$/", " &", implode( "", $val ) );
					}
					else
					{
						$str .= implode( "", $val ) . $char;
					}
				}
			}
	
			return $str;
		}
		else
		{
			return preg_replace( "#([^\s<>'\"/\\-\?&\n\r\%]{{$breakpoint}})#i", "\\1{$char}", $text );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Truncate text string
	/*-------------------------------------------------------------------------*/
	
	/**
	* Truncate a HTML string without breaking HTML entites
	*
	* @param	string	Input String
	* @param	integer	Desired min. length
	* @return	string	Parsed string
	* @since	2.0
	*/
	
	function txt_truncate($text, $limit=30)
	{
		$text = str_replace( '&amp;' , '&#38;', $text );
		$text = str_replace( '&quot;', '&#34;', $text );
		
		$start_text = $text;
		
		$string_length = $this->txt_mb_strlen( $text );
		
		if ( $string_length > $limit)
		{
			$text = $this->txt_mbsubstr( $text, 0, $limit );
		}
		
		if( $text != $start_text )
		{
			$text .= "...";
			
			$text = preg_replace( "/&(#{0,}([a-zA-Z0-9]+?)?)?\.\.\.$/", '...', $text );
		}
		else
		{
			$text = preg_replace( "/&(#{0,}([a-zA-Z0-9]+?)?)?$/", '', $text );
		}
		
		return $text;
	}
	
	
	/*-------------------------------------------------------------------------*/
	// Multibyte safe substr
	/*-------------------------------------------------------------------------*/
	
	/**
	* Substr support for this without mb_substr
	*
	* @param	string	Input String
	* @param	integer	Desired min. length
	* @return	string	Parsed string
	* @since	2.0
	*/
	
	function txt_mbsubstr($text, $start=0, $limit=30)
	{
		$text = str_replace( '&amp;' , '&#38;', $text );
		$text = str_replace( '&quot;', '&#34;', $text );
		
		// Do we have multi-byte functions?
		
		if( function_exists('mb_list_encodings') )
		{
			// PHP 5, preferred method
			
			$valid_encodings = array();
			$valid_encodings = mb_list_encodings();
			
			if( count($valid_encodings) )
			{
				if( in_array( strtoupper($this->vars['gb_char_set']), $valid_encodings ) )
				{
					return mb_substr( $text, $start, $limit, strtoupper($this->vars['gb_char_set']) );
				}
			}
		}
		else if( function_exists('mb_substr') )
		{
			// PHP 4 support
			
			// Though this is quite tedious, let's check to see if encoding is supported....
			// http://us2.php.net/manual/en/ref.mbstring.php
			
			$valid_encodings = array( 'UCS-4', 'UCS-4BE', 'UCS-4LE', 'UCS-2', 'UCS-2BE', 'UCS-2LE', 'UTF-32', 'UTF-32BE',
										'UTF-32LE', 'UTF-16', 'UTF-16BE', 'UTF-16LE', 'UTF-7', 'UTF7-IMAP', 'UTF-8',
										'ASCII', 'EUC-JP', 'SJIS', 'EUCJP-WIN', 'SJIS-WIN', 'ISO-2022-JP', 'JIS', 'ISO-8859-1',
										'ISO-8859-2', 'ISO-8859-3', 'ISO-8859-4', 'ISO-8859-5', 'ISO-8859-6', 'ISO-8859-7',
										'ISO-8859-8', 'ISO-8859-9', 'ISO-8859-10', 'ISO-8859-13', 'ISO-8859-14', 'ISO-8859-15',
										'EUC-CN', 'CP936', 'EUC-TW', 'HZ', 'CP950', 'BIG-5', 'EUC-KR', 'CP949', 'ISO-2022-KR',
										'WINDOWS-1251', 'CP1251', 'WINDOWS-1252', 'CP1252', 'CP866', 'KOI8-R' );

			if( in_array( strtoupper($this->vars['gb_char_set']), $valid_encodings ) )
			{
				return mb_substr( $text, $start, $limit, strtoupper($this->vars['gb_char_set']) );
			}
		}
		
		// No?  Let's do our handrolled method then
	
		$string_length = $this->txt_mb_strlen( $text );
		
		if ( $string_length > $limit)
		{
			if( strtoupper($this->vars['gb_char_set']) == 'UTF-8' )
			{
				// Multi-byte support
				
				$text = @preg_replace('#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,0}'.
	                       '((?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){'.intval($start).','.intval($limit).'}).*#s',
	                       '$1',$text);
            }
            else
            {
	            $text = substr( $text, $start, $limit );
            }

			$text = preg_replace( "/&(#(\d+?)?)?$/", '', $text );
		}
		else
		{
			$text = preg_replace( "/&(#(\d+?)?)?$/", '', $text );
		}
		
		return $text;
	}	
	
	/*-------------------------------------------------------------------------*/
	// txt_filename_clean
	// ------------------
	// Clean filenames...
	/*-------------------------------------------------------------------------*/
	
	/**
	* Clean a string to prevent file traversal issues
	*
	* @param	string	Input String
	* @return	string	Parsed string
	* @since	2.1
	*/
	function txt_filename_clean( $t )
	{
		$t = $this->txt_alphanumerical_clean( $t, '.' );
		$t = preg_replace( '#\.{1,}#s', '.', $t );
		
		return $t;
    }
	
	/*-------------------------------------------------------------------------*/
	// txt_md5_clean
	// ------------------
	// Clean up MD5 hashes
	/*-------------------------------------------------------------------------*/
	
	/**
	* Returns a cleaned MD5 hash
	*
	* @param	string	Input String
	* @return	string	Parsed string
	* @since	2.1
	*/
	function txt_md5_clean( $t )
	{
		return preg_replace( "/[^a-zA-Z0-9]/", "" , substr( $t, 0, 32 ) );
    }

	/*-------------------------------------------------------------------------*/
	// txt_alphanumerical_clean
	// ------------------
	// Remove non alphas
	/*-------------------------------------------------------------------------*/
	
	/**
	* Clean a string to remove all non alphanumeric characters
	*
	* @param	string	Input String
	* @param	string	Additional tags
	* @return	string	Parsed string
	* @since	2.1
	*/
	function txt_alphanumerical_clean( $t, $additional_tags="" )
	{
		if ( $additional_tags )
		{
			$additional_tags = preg_quote( $additional_tags, "/" );
		}
		
		return preg_replace( "/[^a-zA-Z0-9\-\_".$additional_tags."]/", "" , $t );
    }
    
	/*-------------------------------------------------------------------------*/
	// txt_mb_strlen
	// ------------------
	// Multi byte char string length
	/*-------------------------------------------------------------------------*/
	
	/**
	* Get the true length of a multi-byte character string
	*
	* @param	string	Input String
	* @return	intger	String length
	* @since	2.1
	*/
	function txt_mb_strlen( $t )
	{
		return strlen( preg_replace("/&#([0-9]+);/", "-", $this->txt_stripslashes( $t ) ) );
    }
    
	/*-------------------------------------------------------------------------*/
	// txt_stripslashes
	// ------------------
	// Make Big5 safe - only strip if not already...
	/*-------------------------------------------------------------------------*/
	
	/**
	* Remove slashes if magic_quotes enabled
	*
	* @param	string	Input String
	* @return	string	Parsed string
	* @since	2.0
	*/
	function txt_stripslashes($t)
	{
		if ( $this->get_magic_quotes )
		{
    		$t = stripslashes($t);
    		$t = preg_replace( "/\\\(?!&amp;#|\?#)/", "&#092;", $t );
    	}
    	
    	return $t;
    }
	
	/*-------------------------------------------------------------------------*/
	// txt_raw2form
	// ------------------
	// makes _POST text safe for text areas
	/*-------------------------------------------------------------------------*/
	
	/**
	* Convert text for use in a textarea
	*
	* @param	string	Input String
	* @return	string	Parsed string
	* @since	2.0
	*/
	function txt_raw2form($t="")
	{
		$t = str_replace( '$', "&#036;", $t);
			
		if ( $this->get_magic_quotes )
		{
			$t = stripslashes($t);
		}
		
		$t = preg_replace( "/\\\(?!&amp;#|\?#)/", "&#092;", $t );
		
		//---------------------------------------
		// Make sure macros aren't converted
		//---------------------------------------
		
		$t = preg_replace( "/<{(.+?)}>/", "&lt;{\\1}&gt;", $t );
		
		return $t;
	}
	
	/*-------------------------------------------------------------------------*/
	// txt_form2raw
	// ------------------
	// Converts textarea to raw
	/*-------------------------------------------------------------------------*/
	
	/**
	* Unconvert text for use in a textarea
	*
	* @param	string	Input String
	* @return	string	Parsed string
	* @since	2.0
	*/
	function txt_form2raw($t="")
	{
		$t = str_replace( "&#036;", '$' , $t);
		$t = str_replace( "&#092;", '\\', $t );
		
		return $t;
	}
	
	/*-------------------------------------------------------------------------*/
	// Safe Slashes - ensures slashes are saved correctly
	/*-------------------------------------------------------------------------*/
	
	/**
	* Attempt to make slashes safe for us in DB (not really needed now?)
	*
	* @param	string	Input String
	* @return	string	Parsed string
	* @since	2.0
	*/
	function txt_safeslashes($t="")
	{
		return str_replace( '\\', "\\\\", $this->txt_stripslashes($t));
	}
	
	/*-------------------------------------------------------------------------*/
	// txt_htmlspecialchars
	// ------------------
	// Custom version of htmlspecialchars to take into account mb chars
	/*-------------------------------------------------------------------------*/
	
	/**
	* htmlspecialchars including multi-byte characters
	*
	* @param	string	Input String
	* @return	string	Parsed string
	* @since	2.0
	*/
	function txt_htmlspecialchars($t="")
	{
		// Use forward look up to only convert & not &#123;
		$t = preg_replace("/&(?!#[0-9]+;)/s", '&amp;', $t );
		$t = str_replace( "<", "&lt;"  , $t );
		$t = str_replace( ">", "&gt;"  , $t );
		$t = str_replace( '"', "&quot;", $t );
		$t = str_replace( "'", '&#039;', $t );
		
		return $t; // A nice cup of?
	}
	
	/*-------------------------------------------------------------------------*/
	// txt_UNhtmlspecialchars
	// ------------------
	// Undoes what the above function does. Yes.
	/*-------------------------------------------------------------------------*/
	
	/**
	* unhtmlspecialchars including multi-byte characters
	*
	* @param	string	Input String
	* @return	string	Parsed string
	* @since	2.0
	*/
	function txt_UNhtmlspecialchars($t="")
	{
		$t = str_replace( "&amp;" , "&", $t );
		$t = str_replace( "&lt;"  , "<", $t );
		$t = str_replace( "&gt;"  , ">", $t );
		$t = str_replace( "&quot;", '"', $t );
		$t = str_replace( "&#039;", "'", $t );
		
		return $t;
	}
	
	/*-------------------------------------------------------------------------*/
	// txt_wintounix
	// ------------------
	// Converts \r\n to \n
	/*-------------------------------------------------------------------------*/
	
	/**
	* Convert windows newlines to unix newlines
	*
	* @param	string	Input String
	* @return	string	Parsed string
	* @since	2.0
	*/
	function txt_windowstounix($t="")
	{
		// windows
		$t = str_replace( "\r\n" , "\n", $t );
		// Mac OS 9
		$t = str_replace( "\r"   , "\n", $t );
		return $t;
	}
	
	/*-------------------------------------------------------------------------*/
	// return_md5_check
	// ------------------
	// md5 hash for server side validation of form / link stuff
	/*-------------------------------------------------------------------------*/
	
	/**
	* Return MD5 hash for use in forms
	*
	* @return	string	MD5 hash
	* @since	2.0
	*/
	function return_md5_check()
	{
		if ( $this->member['id'] )
		{
			return md5( $this->member['email'].'&'.$this->member['member_login_key'].'&'.$this->member['joined'] );
		}
		else
		{
			return md5("this is only here to prevent it breaking on guests");
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// C.O.C.S (clean old comma-delimeted strings)
	// ------------------
	// <>
	/*-------------------------------------------------------------------------*/
	
	/**
	* Remove leading comma from comma delim string
	*
	* @param	string	Input String
	* @return	string	Parsed string
	* @since	2.0
	*/
	function trim_leading_comma($t)
	{
		return preg_replace( "/^,/", "", $t );
	}
	
	/**
	* Remove trailing comma from comma delim string
	*
	* @param	string	Input String
	* @return	string	Parsed string
	* @since	2.0
	*/
	function trim_trailing_comma($t)
	{
		return preg_replace( "/,$/", "", $t );
	}
	
	/**
	* Remove dupe commas from comma delim string
	*
	* @param	string	Input String
	* @return	string	Parsed string
	* @since	2.0
	*/
	function clean_comma($t)
	{
		return preg_replace( "/,{2,}/", ",", $t );
	}
	
	/**
	* Clean perm string (wrapper for comma cleaners)
	*
	* @param	string	Input String
	* @return	string	Parsed string
	* @since	2.0
	*/
	function clean_perm_string($t)
	{
		$t = $this->clean_comma($t);
		$t = $this->trim_leading_comma($t);
		$t = $this->trim_trailing_comma($t);
		
		return $t;
	}
	
	/*-------------------------------------------------------------------------*/
	// Calculate max post size
	/*-------------------------------------------------------------------------*/
	
	function math_get_post_max_size()
	{
		$max_file_size = 16777216;
		$tmp           = 0;
		
		$_post   = @ini_get('post_max_size');
		$_upload = @ini_get('upload_max_filesize');
		
		if ( $_upload > $_post )
		{
			$tmp = $_post;
		}
		else
		{
			$tmp = $_upload;
		}
		
		if ( $tmp )
		{
			$max_file_size = $tmp;
			unset($tmp);
			
			preg_match( "#^(\d+)(\w+)$#", strtolower($max_file_size), $match );
			
			if ( $match[2] == 'm' )
			{
				$max_file_size = intval( $max_file_size ) * 1024 * 1024;
			}
			else if ( $match[2] == 'k' )
			{
				$max_file_size = intval( $max_file_size ) * 1024;
			}
			else
			{
				$max_file_size = intval( $max_file_size );
			}
		}

		return $max_file_size;
	}
	
	/*-------------------------------------------------------------------------*/
    // math_strlen_to_bytes
    /*-------------------------------------------------------------------------*/
    
    /**
	* Convert strlen to bytes
	*
	* @param	integer	string length (no chars)
	* @return	integer	Bytes
	* @since	2.0
	*/
	function math_strlen_to_bytes( $strlen=0 )
    {
		$dh = pow(10, 0);
        
        return round( $strlen / ( pow(1024, 0) / $dh ) ) / $dh;
    }
    
	/*-------------------------------------------------------------------------*/
	// size_format
	// ------------------
	// Give it a byte to eat and it'll return nice stuff!
	/*-------------------------------------------------------------------------*/
	
	/**
	* Convert bytes into kb, mb, etc
	*
	* @param	integer	size in bytes
	* @return	string	Human size
	* @since	2.0
	*/
	function size_format($bytes="")
	{
		$retval = "";
		
		if ($bytes >= 1048576)
		{
			$retval = round($bytes / 1048576 * 100 ) / 100 . $this->lang['sf_mb'];
		}
		else if ($bytes  >= 1024)
		{
			$retval = round($bytes / 1024 * 100 ) / 100 . $this->lang['sf_k'];
		}
		else
		{
			$retval = $bytes . $this->lang['sf_bytes'];
		}
		
		return $retval;
	}
	
	/*-------------------------------------------------------------------------*/
	// print_forum_rules
	// ------------------
	// Checks and prints forum rules (if required)
	/*-------------------------------------------------------------------------*/
	
	/**
	* Universal routine for printing forum rules
	*
	* @param	array	Forum data
	* @return	string	Formatted rules HTML in wrapper
	* @since	2.0
	*/
	function print_forum_rules($forum)
	{
		$ruleshtml    = "";
		$rules['fid'] = $forum['id'];
		
		if ( isset($forum['show_rules']) AND $forum['show_rules'] )
		{
			 if ( $forum['show_rules'] == 2 )
			 {
				if ( isset($this->vars['forum_cache_minimum']) AND $this->vars['forum_cache_minimum'] )
				{
					$tmp = $this->DB->simple_exec_query( array( 'select' => 'rules_title, rules_text', 'from' => 'forums', 'where' => "id=".$forum['id']) );
					$rules['title'] = $tmp['rules_title'];
			 		$rules['body']  = $tmp['rules_text'];
				}
				else
				{
					$tmp = $this->DB->simple_exec_query( array( 'select' => 'rules_text', 'from' => 'forums', 'where' => "id=".$forum['id']) );
			 		$rules['body']  = $tmp['rules_text'];
			 		$rules['title'] = $forum['rules_title'];
				}
				
				$ruleshtml = $this->compiled_templates['skin_global']->forum_show_rules_full($rules);
			 }
			 else
			 {
			 	if ( isset( $this->vars['forum_cache_minimum'] ) AND $this->vars['forum_cache_minimum'] )
				{
					$tmp = $this->DB->simple_exec_query( array( 'select' => 'rules_title', 'from' => 'forums', 'where' => "id=".$forum['id']) );
					$rules['title'] = $tmp['rules_title'];
				}
				else
				{
			 		$rules['title'] = $forum['rules_title'];
				}
				
				$ruleshtml = $this->compiled_templates['skin_global']->forum_show_rules_link($rules);
			 }
		}
		
		return $ruleshtml;
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// hdl_ban_line() : Get / set ban info
	// Returns array on get and string on "set"
	//
	/*-------------------------------------------------------------------------*/
	
	/**
	* Get / set member's ban info
	*
	* @param	array	Ban info (unit, timespan, date_end, date_start)
	* @return	mixed
	* @since	2.0
	*/
	function hdl_ban_line($bline)
	{
		if ( is_array( $bline ) )
		{
			// Set ( 'timespan' 'unit' )
			
			$factor = $bline['unit'] == 'd' ? 86400 : 3600;
			
			$date_end = time() + ( $bline['timespan'] * $factor );
			
			return time() . ':' . $date_end . ':' . $bline['timespan'] . ':' . $bline['unit'];
		}
		else
		{
			$arr = array();
			
			list( $arr['date_start'], $arr['date_end'], $arr['timespan'], $arr['unit'] ) = explode( ":", $bline );
			
			return $arr;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// check_perms() : Nice little sub to check perms
	// Returns TRUE if access is allowed, FALSE if not.
	//
	/*-------------------------------------------------------------------------*/
	
	/**
	* Check forum permissions
	*
	* @param	string	Comma delim. forum permission string (2,4,5,6)
	* @return	boolean
	* @since	2.0
	*/
	function check_perms($forum_perm="")
	{
		if ( ! is_array( $this->perm_id_array ) )
		{
			return FALSE;
		}
		
		if ( $forum_perm == "" )
		{
			return FALSE;
		}
		else if ( $forum_perm == '*' )
		{
			return TRUE;
		}
		else
		{
			$forum_perm_array = explode( ",", $forum_perm );
			
			foreach( $this->perm_id_array as $u_id )
			{
				if ( in_array( $u_id, $forum_perm_array ) )
				{
					return TRUE;
				}
			}
			
			// Still here? Not a match then.
			
			return FALSE;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// check_perms() : Nice little sub to check perms
	// Returns TRUE if access is allowed, FALSE if not.
	//
	/*-------------------------------------------------------------------------*/
	
	/**
	* Check forum permissions
	*
	* @param	string	Comma delim. of group IDs (2,4,5,6)
	* @return	string  Comma delim of PERM MASK ids
	* @since	2.1.1
	*/
	function create_perms_from_group( $in_group_ids )
    {
    	$out = "";
    	
    	if ( $in_group_ids == '*' )
    	{
    		foreach( $this->cache['group_cache'] as $data )
			{
				if ( ! $data['g_id'] )
				{
					continue;
				}
				
				//-----------------------------------------
				// Got a perm mask?
				//-----------------------------------------
				
				if ( $data['g_perm_id'] )
				{
					$out .= ',' . $data['g_perm_id'];
				}
			}
    	}
    	else if ( $in_group_ids )
		{
			$groups_id = explode( ',', $in_group_ids );
			
			if ( count( $groups_id ) )
			{
				foreach( $groups_id as $pid )
				{
					if ( ! $this->cache['group_cache'][ $pid ]['g_id'] )
					{
						continue;
					}
					
					//-----------------------------------------
					// Got a perm mask?
					//-----------------------------------------
					
					if ( $this->cache['group_cache'][ $pid ]['g_perm_id'] )
					{
						$out .= ',' . $this->cache['group_cache'][ $pid ]['g_perm_id'];
					}
				}
			}
		}
		
		//-----------------------------------------
		// Tidy perms_id
		//-----------------------------------------
		
		$out = $this->clean_perm_string( $out );
		
		return $out;
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// do_number_format() : Nice little sub to handle common stuff
	//
	/*-------------------------------------------------------------------------*/
	
	/**
	* Wrapper for number_format
	*
	* @param	integer	Number
	* @return	string	Formatted number
	* @since	2.0
	*/
	function do_number_format($number)
	{
		if ( $this->vars['number_format'] != 'none' )
		{ 
			return number_format($number , 0, '', $this->num_format);
		}
		else
		{
			return $number;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// hdl_forum_read_cookie()
	//
	/*-------------------------------------------------------------------------*/
	
	/**
	* Get / set forum read cookie
	*
	* @param	integer	set / get flag
	* @return	boolean
	* @since	2.0
	*/
	function hdl_forum_read_cookie($set="")
	{
		if ( $set == "" )
		{
			// Get cookie and return array...
			
			if ( $fread = $this->my_getcookie('forum_read') )
			{
				if( $fread != "-1" )
				{
					$farray = unserialize(stripslashes($fread));
					
					if ( is_array($farray) and count($farray) > 0 )
					{
						foreach( $farray as $id => $stamp )
						{
							$this->forum_read[ intval($id) ] = intval($stamp);
						}
					}
				}
			}
			
			return TRUE;
		}
		else
		{
			// Set cookie...
			
			$fread = addslashes(serialize($this->forum_read));
			
			$this->my_setcookie('forum_read', $fread);
			
			return TRUE;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// Return scaled down image
	//
	/*-------------------------------------------------------------------------*/
	
	/**
	* Scale image
	*
	* @param	array
	* @todo		Direct to kernel/class_image.php?
	* @return	array
	* @since	2.0
	*/
	function scale_image($arg)
	{
		// max_width, max_height, cur_width, cur_height
		
		$ret = array(
					  'img_width'  => $arg['cur_width'],
					  'img_height' => $arg['cur_height']
					);
		
		if ( $arg['cur_width'] > $arg['max_width'] )
		{
			$ret['img_width']  = $arg['max_width'];
			$ret['img_height'] = ceil( ( $arg['cur_height'] * ( ( $arg['max_width'] * 100 ) / $arg['cur_width'] ) ) / 100 );
			$arg['cur_height'] = $ret['img_height'];
			$arg['cur_width']  = $ret['img_width'];
		}
		
		if ( $arg['cur_height'] > $arg['max_height'] )
		{
			$ret['img_height']  = $arg['max_height'];
			$ret['img_width']   = ceil( ( $arg['cur_width'] * ( ( $arg['max_height'] * 100 ) / $arg['cur_height'] ) ) / 100 );
		}
		
		return $ret;
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// Show NORMAL created security image(s)...
	//
	/*-------------------------------------------------------------------------*/
	
	/**
	* Show anti-spam bot GIF image numbers
	*
	* @param	integer	Current number to show
	* @return	void
	* @since	2.0
	*/
	function show_gif_img($this_number="")
	{
		$numbers = array( 0 => 'R0lGODlhCAANAJEAAAAAAP////4BAgAAACH5BAQUAP8ALAAAAAAIAA0AAAIUDH5hiKsOnmqSPjtT1ZdnnjCUqBQAOw==',
						  1 => 'R0lGODlhCAANAJEAAAAAAP////4BAgAAACH5BAQUAP8ALAAAAAAIAA0AAAIUjAEWyMqoXIprRkjxtZJWrz3iCBQAOw==',
						  2 => 'R0lGODlhCAANAJEAAAAAAP////4BAgAAACH5BAQUAP8ALAAAAAAIAA0AAAIUDH5hiKubnpPzRQvoVbvyrDHiWAAAOw==',
						  3 => 'R0lGODlhCAANAJEAAAAAAP////4BAgAAACH5BAQUAP8ALAAAAAAIAA0AAAIVDH5hiKbaHgRyUZtmlPtlfnnMiGUFADs=',
						  4 => 'R0lGODlhCAANAJEAAAAAAP////4BAgAAACH5BAQUAP8ALAAAAAAIAA0AAAIVjAN5mLDtjFJMRjpj1Rv6v1SHN0IFADs=',
						  5 => 'R0lGODlhCAANAJEAAAAAAP////4BAgAAACH5BAQUAP8ALAAAAAAIAA0AAAIUhA+Bpxn/DITL1SRjnps63l1M9RQAOw==',
						  6 => 'R0lGODlhCAANAJEAAAAAAP////4BAgAAACH5BAQUAP8ALAAAAAAIAA0AAAIVjIEYyWwH3lNyrQTbnVh2Tl3N5wQFADs=',
						  7 => 'R0lGODlhCAANAJEAAAAAAP////4BAgAAACH5BAQUAP8ALAAAAAAIAA0AAAIUhI9pwbztAAwP1napnFnzbYEYWAAAOw==',
						  8 => 'R0lGODlhCAANAJEAAAAAAP////4BAgAAACH5BAQUAP8ALAAAAAAIAA0AAAIVDH5hiKubHgSPWXoxVUxC33FZZCkFADs=',
						  9 => 'R0lGODlhCAANAJEAAAAAAP////4BAgAAACH5BAQUAP8ALAAAAAAIAA0AAAIVDA6hyJabnnISnsnybXdS73hcZlUFADs=',
						);
		
		@header("Content-Type: image/gif");
		echo base64_decode($numbers[ $this_number ]);
		exit();
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// Show GD created security image...
	//
	/*-------------------------------------------------------------------------*/
	
	/**
	* Show anti-spam bot GD image numbers
	*
	* @param	string	Number string
	* @return	void
	* @since	2.0
	*/
	function show_gd_img($content="")
	{
		if ( ! is_object( $this->work_classes['class_captcha'] ) )
		{
			require_once( KERNEL_PATH . 'class_captcha.php' );
			$this->work_classes['class_captcha']                  =  new class_captcha();
			$this->work_classes['class_captcha']->ipsclass        =& $this;
			$this->work_classes['class_captcha']->path_background =  ROOT_PATH . 'style_captcha/captcha_backgrounds';
			$this->work_classes['class_captcha']->path_fonts      =  ROOT_PATH . 'style_captcha/captcha_fonts';
		}
	
		return $this->work_classes['class_captcha']->captcha_show_gd_img( $content );
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// Convert newlines to <br /> nl2br is buggy with <br /> on early PHP builds
	//
	/*-------------------------------------------------------------------------*/
	
	/**
	* <br /> Safe nl2br (Buggy on old PHP builds)
	*
	* @param	string	Input text
	* @return	string	Parsed text
	* @since	2.0
	*/
	function my_nl2br($t="")
	{
		return str_replace( "\n", "<br />", $t );
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// Convert <br /> to newlines
	//
	/*-------------------------------------------------------------------------*/
	
	/**
	* <br /> Safe br2nl
	*
	* @param	string	Input text
	* @return	string	Parsed text
	* @since	2.0
	*/
	function my_br2nl($t="")
	{
		$t = preg_replace( "#(?:\n|\r)?<br />(?:\n|\r)?#", "\n", $t );
		$t = preg_replace( "#(?:\n|\r)?<br>(?:\n|\r)?#"  , "\n", $t );
		
		return $t;
	}
		
	/*-------------------------------------------------------------------------*/
	//
	// Creates a profile link if member is a reg. member, else just show name
	//
	/*-------------------------------------------------------------------------*/
	
	/**
	* Create profile link
	*
	* @param	string	User's display name
	* @param	integer	User's DB ID
	* @return	string	Parsed a href link
	* @since	2.0
	*/
	function make_profile_link($name, $id="")
	{
		if ($id > 0)
		{
			return "<a href='{$this->base_url}showuser=$id'>$name</a>";
		}
		else
		{
			return $name;
		}
	}
	
	
	/*-------------------------------------------------------------------------*/
	//
	// Applies group suffix/prefix to a name
	//
	/*-------------------------------------------------------------------------*/
	
	/**
	* Format name based on group suffix/prefix
	*
	* @param	string	User's display name
	* @param	integer	User's group ID
	* @param	string  Optional prefix override
	* @param	string  Optional suffix override
	* @return	string	Formatted name
	* @since	2.2
	*/
	function make_name_formatted($name, $group_id="", $prefix="", $suffix="")
	{
		if ( isset( $this->vars['ipb_disable_group_psformat'] ) and $this->vars['ipb_disable_group_psformat']  )
		{
			return $name;
		}
		
		if( !$group_id )
		{
			$group_id = 0;
		}
		
		if( !$prefix )
		{
			if( $this->cache['group_cache'][ $group_id ]['prefix'] )
			{
				$prefix = $this->cache['group_cache'][ $group_id ]['prefix'];
			}
		}
		
		if( !$suffix )
		{
			if( $this->cache['group_cache'][ $group_id ]['suffix'] )
			{
				$suffix = $this->cache['group_cache'][ $group_id ]['suffix'];
			}
		}		
		
		return $prefix.$name.$suffix;
	}
		
	
	/*-------------------------------------------------------------------------*/
	//
	// Redirect using HTTP commands, not a page meta tag.
	//
	/*-------------------------------------------------------------------------*/
	
	/**
	* HTTP redirect with out message screen
	*
	* @param	string	URL to load
	* @return	void
	* @since	2.0
	*/
	function boink_it($url)
	{
		// Ensure &amp;s are taken care of
		
		$url = str_replace( "&amp;", "&", $url );
		
		if ($this->vars['header_redirect'] == 'refresh')
		{
			@header("Refresh: 0;url=".$url);
		}
		else if ($this->vars['header_redirect'] == 'html')
		{
			$url = str_replace( '&', '&amp;', str_replace( '&amp;', '&', $url ) );
			echo("<html><head><meta http-equiv='refresh' content='0; url=$url'></head><body></body></html>");
			exit();
		}
		else
		{
			@header("Location: ".$url);
		}
		exit();
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// Create a random 8 character password 
	//
	/*-------------------------------------------------------------------------*/
	
	/**
	* Create a random 15 character password
	*
	* @todo		check if we still need this?
	* @return	string	Password
	* @since	2.0
	*/
	function make_password()
	{
		$pass = "";
		
		// Want it random you say, eh?
		// (enter evil laugh)
		
		$unique_id 	= uniqid( mt_rand(), TRUE );
		$prefix		= $this->converge->generate_password_salt();
		$unique_id .= md5( $prefix );
		
		usleep( mt_rand(15000,1000000) );
		// Hmm, wonder how long we slept for
		
		mt_srand( (double)microtime()*1000000 );
		$new_uniqueid = uniqid( mt_rand(), TRUE );
		
		$final_rand = md5( $unique_id.$new_uniqueid );
		
		mt_srand(); // Wipe out the seed
		
		for ($i = 0; $i < 15; $i++)
		{
			$pass .= $final_rand{ mt_rand(0, 31) };
		}
	
		return $pass;
	}
    
	/*-------------------------------------------------------------------------*/
	//
	// Generate the appropriate folder icon for a topic
	//
	/*-------------------------------------------------------------------------*/
	
	/**
	* Generate the appropriate folder icon for a topic
	*
	* @param	array	Topic data array
	* @param	string	Dot flag
	* @param	integer	Last read time
	* @return	string	Parsed macro
	* @since	2.0
	*/
	function folder_icon($topic, $dot="", $last_time)
	{
		//-----------------------------------------
		// Sort dot
		//-----------------------------------------
		
		if ($dot != "")
		{
			$dot = "_DOT";
		}
		
		if ($topic['state'] == 'closed')
		{
			return "<{B_LOCKED}>";
		}
		
		if ($topic['poll_state'])
		{
			if ( ! $this->member['id'] )
			{
				return "<{B_POLL_NN".$dot."}>";
			}
			
			if ($last_time  && ($topic['last_vote'] > $last_time ))
			{
				return "<{B_POLL".$dot."}>";
			}
			
			return "<{B_POLL_NN".$dot."}>";
		}
		
		
		if ($topic['state'] == 'moved' or $topic['state'] == 'link')
		{
			return "<{B_MOVED}>";
		}
		
		if ( ! $this->member['id'] )
		{
			if ($topic['posts'] + 1 >= $this->vars['hot_topic'])
			{
				return "<{B_HOT_NN".$dot."}>";
			}
			else
			{
				return "<{B_NORM".$dot."}>";
			}
		}

		if (($topic['posts'] + 1 >= $this->vars['hot_topic']) and ( (isset($last_time) )  && ($topic['last_post'] <= $last_time )))
		{
			return "<{B_HOT_NN".$dot."}>";
		}
		if ($topic['posts'] + 1 >= $this->vars['hot_topic'])
		{
			return "<{B_HOT".$dot."}>";
		}
		if ($last_time  && ($topic['last_post'] > $last_time))
		{
			return "<{B_NEW".$dot."}>";
		}
		
		return "<{B_NORM".$dot."}>";
	}
	
	/*-------------------------------------------------------------------------*/
    // text_tidy:
    // Takes raw text from the DB and makes it all nice and pretty - which also
    // parses un-HTML'd characters. Use this with caution!         
    /*-------------------------------------------------------------------------*/
    
    /**
	* Takes raw text from the DB and makes it all nice and pretty - which also
	* parses un-HTML'd characters. Use this with caution! 
	*
	* @param	string	Raw text
	* @return	string	Parsed text
	* @since	2.0
	* @todo			Check if we still need this
	* @deprecated	Since IPB 2.1
	*/
    function text_tidy($txt = "") {
    
    	$trans = get_html_translation_table(HTML_ENTITIES);
    	$trans = array_flip($trans);
    	
    	$txt = strtr( $txt, $trans );
    	
    	$txt = preg_replace( "/\s{2}/" , "&nbsp; "      , $txt );
    	$txt = preg_replace( "/\r/"    , "\n"           , $txt );
    	$txt = preg_replace( "/\t/"    , "&nbsp;&nbsp;" , $txt );
    	//$txt = preg_replace( "/\\n/"   , "&#92;n"       , $txt );
    	
    	return $txt;
    }

    /*-------------------------------------------------------------------------*/
    // Build up page span links                
    /*-------------------------------------------------------------------------*/
    
    /**
	* Build up page span links 
	*
	* @param	array	Page data
	* @return	string	Parsed page links HTML
	* @since	2.0
	*/
	function build_pagelinks($data)
	{
		$data['leave_out']    = isset($data['leave_out']) ? $data['leave_out'] : '';
		$data['no_dropdown']  = isset($data['no_dropdown']) ? intval( $data['no_dropdown'] ) : 0;
		$data['USE_ST']		  = isset($data['USE_ST'])	? $data['USE_ST']	 : '';
		$work = array( 'pages' => 0, 'page_span' => '', 'st_dots' => '', 'end_dots' => '' );
		
		$section = !$data['leave_out'] ? 2 : $data['leave_out'];  // Number of pages to show per section( either side of current), IE: 1 ... 4 5 [6] 7 8 ... 10
		
		$use_st  = !$data['USE_ST'] ? 'st' : $data['USE_ST'];
		
		//-----------------------------------------
		// Get the number of pages
		//-----------------------------------------
		
		if ( $data['TOTAL_POSS'] > 0 )
		{
			$work['pages'] = ceil( $data['TOTAL_POSS'] / $data['PER_PAGE'] );
		}
		
		$work['pages'] = $work['pages'] ? $work['pages'] : 1;
		
		//-----------------------------------------
		// Set up
		//-----------------------------------------
		
		$work['total_page']   = $work['pages'];
		$work['current_page'] = $data['CUR_ST_VAL'] > 0 ? ($data['CUR_ST_VAL'] / $data['PER_PAGE']) + 1 : 1;
		
		//-----------------------------------------
		// Next / Previous page linkie poos
		//-----------------------------------------
		
		$previous_link = "";
		$next_link     = "";
		
		if ( $work['current_page'] > 1 )
		{
			$start = $data['CUR_ST_VAL'] - $data['PER_PAGE'];
			$previous_link = $this->compiled_templates['skin_global']->pagination_previous_link("{$data['BASE_URL']}&amp;$use_st=$start");
		}
		
		if ( $work['current_page'] < $work['pages'] )
		{
			$start = $data['CUR_ST_VAL'] + $data['PER_PAGE'];
			$next_link = $this->compiled_templates['skin_global']->pagination_next_link("{$data['BASE_URL']}&amp;$use_st=$start");
		}
		
		//-----------------------------------------
		// Loppy loo
		//-----------------------------------------
		
		if ($work['pages'] > 1)
		{
			$work['first_page'] = $this->compiled_templates['skin_global']->pagination_make_jump($work['pages'], $data['no_dropdown']);
			
			if ( 1 < ($work['current_page'] - $section))
			{
				$work['st_dots'] = $this->compiled_templates['skin_global']->pagination_start_dots($data['BASE_URL']);
			}
			
			for( $i = 0, $j= $work['pages'] - 1; $i <= $j; ++$i )
			{
				$RealNo = $i * $data['PER_PAGE'];
				$PageNo = $i+1;
				
				if ($RealNo == $data['CUR_ST_VAL'])
				{
					$work['page_span'] .=  $this->compiled_templates['skin_global']->pagination_current_page( ceil( $PageNo ) );
				}
				else
				{
					if ($PageNo < ($work['current_page'] - $section))
					{
						// Instead of just looping as many times as necessary doing nothing to get to the next appropriate number, let's just skip there now
						$i = $work['current_page'] - $section - 2;
						continue;
					}
					
					// If the next page is out of our section range, add some dotty dots!
					
					if ($PageNo > ($work['current_page'] + $section))
					{
						$work['end_dots'] = $this->compiled_templates['skin_global']->pagination_end_dots("{$data['BASE_URL']}&amp;$use_st=".($work['pages']-1) * $data['PER_PAGE']);
						break;
					}
					
					$work['page_span'] .= $this->compiled_templates['skin_global']->pagination_page_link("{$data['BASE_URL']}&amp;$use_st={$RealNo}", ceil( $PageNo ) );
				}
			}
			
			$work['return']    = $this->compiled_templates['skin_global']->pagination_compile($work['first_page'],$previous_link,$work['st_dots'],$work['page_span'],$work['end_dots'],$next_link,$data['TOTAL_POSS'],$data['PER_PAGE'], $data['BASE_URL'], $data['no_dropdown'], $use_st);
		}
		else
		{
			$work['return']    = $data['L_SINGLE'];
		}
	
		return $work['return'];
	}
    
    /*-------------------------------------------------------------------------*/
    // Build the forum jump menu               
    /*-------------------------------------------------------------------------*/ 
    
    /**
	* Build <select> jump menu
	* $html = 0 means don't return the select html stuff
	* $html = 1 means return the jump menu with select and option stuff
	*
	* @param	integer	HTML flag (see above)
	* @param	integer	Override flag
	* @param	integer
	* @return	string	Parsed HTML
	* @since	2.0
	*/
	function build_forum_jump($html=1, $override=0, $remove_redirects=0)
	{
		$the_html = "";
		
		if ($html == 1) {
		
			$the_html = "<form onsubmit=\"if(document.jumpmenu.f.value == -1){return false;}\" action='{$this->base_url}act=SF' method='get' name='jumpmenu'>
			             <input type='hidden' name='act' value='SF' />\n<input type='hidden' name='s' value='{$this->session_id}' />
			             <select name='f' onchange=\"if(this.options[this.selectedIndex].value != -1){ document.jumpmenu.submit() }\" class='dropdown'>
			             <optgroup label=\"{$this->lang['sj_title']}\">
			              <option value='sj_home'>{$this->lang['sj_home']}</option>
			              <option value='sj_search'>{$this->lang['sj_search']}</option>
			              <option value='sj_help'>{$this->lang['sj_help']}</option>
			             </optgroup>
			             <optgroup label=\"{$this->lang['forum_jump']}\">";
		}
		
		$the_html .= $this->forums->forums_forum_jump($html, $override, $remove_redirects);
			
		if ($html == 1)
		{
			$the_html .= "</optgroup>\n</select>&nbsp;<input type='submit' value='{$this->lang['jmp_go']}' class='button' /></form>";
		}
		
		return $the_html;
	}
	
	/*-------------------------------------------------------------------------*/
	// Clean email
	/*-------------------------------------------------------------------------*/
	
	/**
	* Clean email address
	*
	* @param	string	Email address
	* @return	mixed
	* @since	2.0
	*/
	function clean_email($email = "")
	{
		$email = trim($email);
		
		$email = str_replace( " ", "", $email );
		
		//-----------------------------------------
		// Check for more than 1 @ symbol
		//-----------------------------------------
		
		if ( substr_count( $email, '@' ) > 1 )
		{
			return FALSE;
		}
		
    	$email = preg_replace( "#[\;\#\n\r\*\'\"<>&\%\!\(\)\{\}\[\]\?\\/\s]#", "", $email );
    	
    	if ( preg_match( "/^.+\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,4}|[0-9]{1,4})(\]?)$/", $email) )
    	{
    		return $email;
    	}
    	else
    	{
    		return FALSE;
    	}
	}
    
    /*-------------------------------------------------------------------------*/
    // Return a date or '--' if the date is undef.  
    /*-------------------------------------------------------------------------*/    
    
    /**
	* Generate Human formatted date string
	* Return a date or '--' if the date is undef.
    * We use the rather nice gmdate function in PHP to synchronise our times
    * with GMT. This gives us the following choices:
    * If the user has specified a time offset, we use that. If they haven't set
    * a time zone, we use the default board time offset (which should automagically
    * be adjusted to match gmdate.         
	*
	* @param	integer Unix date
	* @param	method	LONG, SHORT, JOINED, TINY
	* @param	integer
	* @param	integer
	* @return	string	Parsed time
	* @since	2.0
	*/
    function get_date($date, $method, $norelative=0, $full_relative=0)
    {
	    $this->time_options[$method] = str_replace( "&#092;", "\\", $this->time_options[$method] );
	    
        if ( ! $date )
        {
            return '--';
        }
        
        if ( empty($method) )
        {
        	$method = 'LONG';
        }
        
        if ($this->offset_set == 0)
        {
        	// Save redoing this code for each call, only do once per page load
        	
			$this->offset = $this->get_time_offset();
			
			if ( $this->vars['time_use_relative'] )
			{
				$this->today_time     = gmdate('d,m,Y', ( time() + $this->offset) );
				$this->yesterday_time = gmdate('d,m,Y', ( (time() - 86400) + $this->offset) );
			}	
			
			$this->offset_set = 1;
        }
        
        //-----------------------------------------
        // Full relative?
        //-----------------------------------------
        
        if ( $this->vars['time_use_relative'] == 3 )
        {
        	$full_relative = 1;
        }
        
        //-----------------------------------------
        // FULL Relative
        //-----------------------------------------
        
        if ( $full_relative and ( $norelative != 1 ) )
		{
			$diff = time() - $date;
			
			if ( $diff < 3600 )
			{
				if ( $diff < 120 )
				{
					return $this->lang['time_less_minute'];
				}
				else
				{
					return sprintf( $this->lang['time_minutes_ago'], intval($diff / 60) );
				}
			}
			else if ( $diff < 7200 )
			{
				return $this->lang['time_less_hour'];
			}
			else if ( $diff < 86400 )
			{
				return sprintf( $this->lang['time_hours_ago'], intval($diff / 3600) );
			}
			else if ( $diff < 172800 )
			{
				return $this->lang['time_less_day'];
			}
			else if ( $diff < 604800 )
			{
				return sprintf( $this->lang['time_days_ago'], intval($diff / 86400) );
			}
			else if ( $diff < 1209600 )
			{
				return $this->lang['time_less_week'];
			}
			else if ( $diff < 3024000 )
			{
				return sprintf( $this->lang['time_weeks_ago'], intval($diff / 604900) );
			}
			else
			{
				return gmdate($this->time_options[$method], ($date + $this->offset) );
			}
		}
		
		//-----------------------------------------
		// Yesterday / Today
		//-----------------------------------------
		
		else if ( $this->vars['time_use_relative'] and ( $norelative != 1 ) )
		{
			$this_time = gmdate('d,m,Y', ($date + $this->offset) );
			
			//-----------------------------------------
			// Use level 2 relative?
			//-----------------------------------------
			
			if ( $this->vars['time_use_relative'] == 2 )
			{
				$diff = time() - $date;
			
				if ( $diff < 3600 )
				{
					if ( $diff < 120 )
					{
						return $this->lang['time_less_minute'];
					}
					else
					{
						return sprintf( $this->lang['time_minutes_ago'], intval($diff / 60) );
					}
				}
			}
			
			//-----------------------------------------
			// Still here? 
			//-----------------------------------------
			
			if ( $this_time == $this->today_time )
			{
				return str_replace( '{--}', $this->lang['time_today'], gmdate($this->vars['time_use_relative_format'], ($date + $this->offset) ) );
			}
			else if  ( $this_time == $this->yesterday_time )
			{
				return str_replace( '{--}', $this->lang['time_yesterday'], gmdate($this->vars['time_use_relative_format'], ($date + $this->offset) ) );
			}
			else
			{
				return gmdate($this->time_options[$method], ($date + $this->offset) );
			}
		}
		
		//-----------------------------------------
		// Normal
		//-----------------------------------------
		
		else
		{
        	return gmdate($this->time_options[$method], ($date + $this->offset) );
        }
    }
    
    /*-------------------------------------------------------------------------*/
    // Returns the time - tick tock, etc           
    /*-------------------------------------------------------------------------*/   
    
    /**
	* Return current TIME (not date)
	*
	* @param	integer	Unix date
	* @param	string	PHP date() method
	* @return	string
	* @since	2.0
	*/
    function get_time($date, $method='h:i A')
    {
        if ($this->offset_set == 0)
        {
        	// Save redoing this code for each call, only do once per page load
        	
			$this->offset = $this->get_time_offset();
			
			$this->offset_set = 1;
        }
        
        return gmdate($method, ($date + $this->offset) );
    }
    
    /*-------------------------------------------------------------------------*/
    // Returns the offset needed and stuff - quite groovy.              
    /*-------------------------------------------------------------------------*/    
    
    /**
	* Calculates the user's time offset
	*
	* @return	integer
	* @since	2.0
	*/
    function get_time_offset()
    {
    	$r = 0;
    	
    	$r = ( (isset($this->member['time_offset']) AND $this->member['time_offset'] != "") ? $this->member['time_offset'] : $this->vars['time_offset'] ) * 3600;
			
		if ( $this->vars['time_adjust'] )
		{
			$r += ($this->vars['time_adjust'] * 60);
		}
		
		if ( isset($this->member['dst_in_use']) AND $this->member['dst_in_use'] )
		{
			$r += 3600;
		}
    	
    	return $r;
    }
    
    /*-------------------------------------------------------------------------*/
    // Converts user's date to GMT unix date
    /*-------------------------------------------------------------------------*/
    
    /**
	* Converts user's date to GMT unix date
	*
	* @param	array	array( 'year', 'month', 'day', 'hour', 'minute' )
	* @return	integer
	* @since	2.0
	*/
    function convert_local_date_to_unix( $time=array() )
    {
    	//-----------------------------------------
    	// Get the local offset
    	//-----------------------------------------
    	
    	$offset = $this->get_time_offset();
    	$time   = gmmktime( intval($time['hour']), intval($time['minute']), 0, intval($time['month']), intval($time['day']), intval($time['year']) );
    	
    	return $time - $offset;
    }
    
    /*-------------------------------------------------------------------------*/
    // Convert unix timestamp into: (no leading zeros)
    /*-------------------------------------------------------------------------*/
    
    /**
	* Convert unix timestamp into: (no leading zeros)
    *
    * Written into separate function to allow for timezone to be used easily
	*
	* @param	integer	Unix date
	* @return	array	array( 'day' => x, 'month' => x, 'year' => x, 'hour' => x, 'minute' => x );
	* @since	2.0
	*/
    function unixstamp_to_human( $unix=0 )
    {
    	$offset = $this->get_time_offset();
    	$tmp    = gmdate( 'j,n,Y,G,i', $unix + $offset );
    	
    	list( $day, $month, $year, $hour, $min ) = explode( ',', $tmp );
  
    	return array( 'day'    => $day,
    				  'month'  => $month,
    				  'year'   => $year,
    				  'hour'   => $hour,
    				  'minute' => $min );
    }
    
    /*-------------------------------------------------------------------------*/
    // My gmmktime() - PHP func seems buggy
    /*-------------------------------------------------------------------------*/ 
    
    /**
	* My gmmktime() - PHP func seems buggy
    *
	*
	* @param	integer
	* @param	integer
	* @param	integer
	* @param	integer
	* @param	integer
	* @param	integer
	* @return	integer
	* @since	2.0
	*/
	function date_gmmktime( $hour=0, $min=0, $sec=0, $month=0, $day=0, $year=0 )
	{
		// Calculate UTC time offset
		$offset = date( 'Z' );
		
		// Generate server based timestamp
		$time   = mktime( $hour, $min, $sec, $month, $day, $year );
		
		// Calculate DST on / off
		$dst    = intval( date( 'I', $time ) - date( 'I' ) );
		
		return $offset + ($dst * 3600) + $time;
	}
	
    /*-------------------------------------------------------------------------*/
    // getdate doesn't work apparently as it doesn't take into account
    // the offset, even when fed a GMT timestamp.
    /*-------------------------------------------------------------------------*/
    
    /**
	* Hand rolled GETDATE method
    *
    * getdate doesn't work apparently as it doesn't take into account
    * the offset, even when fed a GMT timestamp.
	*
	* @param	integer	Unix date
	* @return	array	0, seconds, minutes, hours, mday, wday, mon, year, yday, weekday, month
	* @since	2.0
	*/
    function date_getgmdate( $gmt_stamp )
    {
    	$tmp = gmdate( 'j,n,Y,G,i,s,w,z,l,F,W,M', $gmt_stamp );
    	
    	list( $day, $month, $year, $hour, $min, $seconds, $wday, $yday, $weekday, $fmon, $week, $smon ) = explode( ',', $tmp );
    	
    	return array(  0         => $gmt_stamp,
    				   "seconds" => $seconds, //	Numeric representation of seconds	0 to 59
					   "minutes" => $min,     //	Numeric representation of minutes	0 to 59
					   "hours"	 => $hour,	  //	Numeric representation of hours	0 to 23
					   "mday"	 => $day,     //	Numeric representation of the day of the month	1 to 31
					   "wday"	 => $wday,    //    Numeric representation of the day of the week	0 (for Sunday) through 6 (for Saturday)
					   "mon"	 => $month,   //    Numeric representation of a month	1 through 12
					   "year"	 => $year,    //    A full numeric representation of a year, 4 digits	Examples: 1999 or 2003
					   "yday"	 => $yday,    //    Numeric representation of the day of the year	0 through 365
					   "weekday" => $weekday, //	A full textual representation of the day of the week	Sunday through Saturday
					   "month"	 => $fmon,    //    A full textual representation of a month, such as January or Mar
					   "week"    => $week,    //    Week of the year
					   "smonth"  => $smon
					);
    }
    
    /*-------------------------------------------------------------------------*/
    // Get Environment Variable
    /*-------------------------------------------------------------------------*/  
    
    /**
	* Get an environment variable value
    *
    * Abstract layer allows us to user $_SERVER or getenv()
	*
	* @param	string	Env. Variable key
	* @return	string
	* @since	2.2
	*/
	
    function my_getenv($key)
    {
	    $return = array();
	    
	    if ( is_array( $_SERVER ) AND count( $_SERVER ) )
	    {
		    if( isset( $_SERVER[$key] ) )
		    {
			    $return = $_SERVER[$key];
		    }
	    }
	    
	    if ( ! $return )
	    {
		    $return = getenv($key);
	    }
	    
	    return $return;
    }    
    
    /*-------------------------------------------------------------------------*/
    // Sets a cookie, abstract layer allows us to do some checking, etc                
    /*-------------------------------------------------------------------------*/    
    
    /**
	* Set a cookie
    *
    * Abstract layer allows us to do some checking, etc
	*
	* @param	string	Cookie name
	* @param	string	Cookie value
	* @param	integer	Is sticky flag
	* @return	void
	* @since	2.0
	*/
    function my_setcookie( $name, $value="", $sticky=1, $expires_x_days=0 )
    {
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
        if ( $this->no_print_header )
        {
        	return;
        }
        
		//-----------------------------------------
		// Set vars
		//-----------------------------------------

        if ( $sticky == 1 )
        {
        	$expires = time() + ( 60*60*24*365 );
        }
		else if ( $expires_x_days )
		{
			$expires = time() + ( $expires_x_days * 86400 );
		}
		else
		{
			$expires = FALSE;
		}
		
		//-----------------------------------------
		// Finish up...
		//-----------------------------------------
		
        $this->vars['cookie_domain'] = $this->vars['cookie_domain'] == "" ? ""  : $this->vars['cookie_domain'];
        $this->vars['cookie_path']   = $this->vars['cookie_path']   == "" ? "/" : $this->vars['cookie_path'];
      	
		//-----------------------------------------
		// Set the cookie
		//-----------------------------------------
		
		if ( in_array( $name, $this->sensitive_cookies ) )
		{
			if ( PHP_VERSION < 5.2 )
			{
				if ( $this->vars['cookie_domain'] )
				{
					@setcookie( $this->vars['cookie_id'].$name, $value, $expires, $this->vars['cookie_path'], $this->vars['cookie_domain'] . '; HttpOnly' );
				}
				else
				{
					@setcookie( $this->vars['cookie_id'].$name, $value, $expires, $this->vars['cookie_path'] );
				}
			}
			else
			{
				@setcookie( $this->vars['cookie_id'].$name, $value, $expires, $this->vars['cookie_path'], $this->vars['cookie_domain'], NULL, TRUE );
			}
		}
		else
		{
			@setcookie( $this->vars['cookie_id'].$name, $value, $expires, $this->vars['cookie_path'], $this->vars['cookie_domain']);
		}
    }
    
    /*-------------------------------------------------------------------------*/
    // Cookies, cookies everywhere and not a byte to eat.
    /*-------------------------------------------------------------------------*/  
    
    /**
	* Get a cookie
    *
    * Abstract layer allows us to do some checking, etc
	*
	* @param	string	Cookie name
	* @return	mixed
	* @since	2.0
	*/
	
    function my_getcookie($name)
    {
    	if ( isset($_COOKIE[$this->vars['cookie_id'].$name]) )
    	{
    		if ( ! in_array( $name, array('topicsread', 'forum_read') ) )
    		{
    			return $this->parse_clean_value(urldecode($_COOKIE[$this->vars['cookie_id'].$name]));
    		}
    		else
    		{
    			return urldecode($_COOKIE[$this->vars['cookie_id'].$name]);
    		}
    	}
    	else
    	{
    		return FALSE;
    	}
    }
    
	/*-------------------------------------------------------------------------*/
    // Makes topics read or forum read cookie safe         
    /*-------------------------------------------------------------------------*/
    /**
	* Makes int based arrays safe
	* XSS Fix: Ticket: 243603
	* Problem with cookies allowing SQL code in keys
	*
	* @param	array	Array
	* @return	array	Array (Cleaned)
	* @since	2.1.4(A)
	*/
    function clean_int_array( $array=array() )
    {
		$return = array();
		
		if ( is_array( $array ) and count( $array ) )
		{
			foreach( $array as $k => $v )
			{
				$return[ intval($k) ] = intval($v);
			}
		}
		
		return $return;
	}
		
    /*-------------------------------------------------------------------------*/
    // Makes incoming info "safe"              
    /*-------------------------------------------------------------------------*/
    
    /**
	* Parse _GET _POST data
    *
    * Clean up and unHTML
	*
	* @return	void
	* @since	Time began
	*/
    function parse_incoming()
    {
		//-----------------------------------------
		// Attempt to switch off magic quotes
		//-----------------------------------------

		@set_magic_quotes_runtime(0);

		$this->get_magic_quotes = @get_magic_quotes_gpc();
		
    	//-----------------------------------------
    	// Clean globals, first.
    	//-----------------------------------------
    	
		$this->clean_globals( $_GET );
		$this->clean_globals( $_POST );
		$this->clean_globals( $_COOKIE );
		$this->clean_globals( $_REQUEST );
    	
		# GET first
		$input = $this->parse_incoming_recursively( $_GET, array() );
		
		# Then overwrite with POST
		$input = $this->parse_incoming_recursively( $_POST, $input );
		
		$this->input = $input;
		
		$this->define_indexes();

		unset( $input );
		
		# Assign request method
		$this->input['request_method'] = strtolower($this->my_getenv('REQUEST_METHOD'));
	}
	
    /*-------------------------------------------------------------------------*/
    // Defines those 'undefined' indexes when not present        
    /*-------------------------------------------------------------------------*/
    
    /**
	* Define indexes in input
    *
	*
	* @return	void
	* @since	Now
	*/
    function define_indexes()
    {
		if( !isset($this->input['st']) )
		{
			$this->input['st'] = 0;
		}
		
		if( !isset($this->input['t']) )
		{
			$this->input['t'] = 0;
		}
		
		if( !isset($this->input['p']) )
		{
			$this->input['p'] = 0;
		}
		
		if( !isset($this->input['pid']) )
		{
			$this->input['pid'] = 0;
		}
		
		if( !isset($this->input['gopid']) )
		{
			$this->input['gopid'] = 0;
		}
		
		if( !isset($this->input['L']) )
		{
			$this->input['L'] = 0;
		}
		
		if( !isset($this->input['f']) )
		{
			$this->input['f'] = 0;
		}
		
		if( !isset($this->input['cal_id']) )
		{
			$this->input['cal_id'] = 0;
		}
		
		if( !isset($this->input['code']) )
		{
			$this->input['code'] = '';
		}
		
		if( !isset($this->input['CODE']) )
		{
			$this->input['CODE'] = '';
		}
	}
	
		
	/*-------------------------------------------------------------------------*/
    // parse_incoming_recursively
    /*-------------------------------------------------------------------------*/
	/**
	* Recursively cleans keys and values and
	* inserts them into the input array
	*/
	function parse_incoming_recursively( &$data, $input=array(), $iteration = 0 )
	{
		// Crafty hacker could send something like &foo[][][][][][]....to kill Apache process
		// We should never have an input array deeper than 10..
		
		if( $iteration >= 10 )
		{
			return $input;
		}
		
		if( count( $data ) )
		{
			foreach( $data as $k => $v )
			{
				if ( is_array( $v ) )
				{
					//$input = $this->parse_incoming_recursively( $data[ $k ], $input );
					$input[ $k ] = $this->parse_incoming_recursively( $data[ $k ], array(), $iteration++ );
				}
				else
				{	
					$k = $this->parse_clean_key( $k );
					$v = $this->parse_clean_value( $v );
					
					$input[ $k ] = $v;
				}
			}
		}
		
		return $input;
	}
	
	/*-------------------------------------------------------------------------*/
    // clean_globals
    /*-------------------------------------------------------------------------*/
	/**
	* Performs basic cleaning
	* Null characters, etc
	*/
	function clean_globals( &$data, $iteration = 0 )
	{
		// Crafty hacker could send something like &foo[][][][][][]....to kill Apache process
		// We should never have an globals array deeper than 10..
		
		if( $iteration >= 10 )
		{
			return $data;
		}
		
		if( count( $data ) )
		{
			foreach( $data as $k => $v )
			{
				if ( is_array( $v ) )
				{
					$this->clean_globals( $data[ $k ], $iteration++ );
				}
				else
				{	
					# Null byte characters
					$v = preg_replace( '/\\\0/' , '&#92;&#48;', $v );
					$v = preg_replace( '/\\x00/', '&#92;x&#48;&#48;', $v );
					$v = str_replace( '%00'     , '%&#48;&#48;', $v );
					
					# File traversal
					$v = str_replace( '../'    , '&#46;&#46;/', $v );
					
					$data[ $k ] = $v;
				}
			}
		}
	}
	
    /*-------------------------------------------------------------------------*/
    // Key Cleaner - ensures no funny business with form elements             
    /*-------------------------------------------------------------------------*/
    
    /**
	* Clean _GET _POST key
    *
	* @param	string	Key name
	* @return	string	Cleaned key name
	* @since	2.1
	*/
    function parse_clean_key($key)
    {
    	if ($key == "")
    	{
    		return "";
    	}
    	
    	$key = htmlspecialchars(urldecode($key));
    	$key = str_replace( ".."           , ""  , $key );
    	$key = preg_replace( "/\_\_(.+?)\_\_/"  , ""  , $key );
    	$key = preg_replace( "/^([\w\.\-\_]+)$/", "$1", $key );
    	
    	return $key;
    }
    
    /*-------------------------------------------------------------------------*/
    // Clean evil tags
    /*-------------------------------------------------------------------------*/
    
    /**
	* Clean possible javascipt codes
    *
	* @param	string	Input
	* @return	string	Cleaned Input
	* @since	2.0
	*/
    function clean_evil_tags( $t )
    {
    	$t = preg_replace( "/javascript/i" , "j&#097;v&#097;script", $t );
		$t = preg_replace( "/alert/i"      , "&#097;lert"          , $t );
		$t = preg_replace( "/about:/i"     , "&#097;bout:"         , $t );
		$t = preg_replace( "/onmouseover/i", "&#111;nmouseover"    , $t );
		$t = preg_replace( "/onclick/i"    , "&#111;nclick"        , $t );
		$t = preg_replace( "/onload/i"     , "&#111;nload"         , $t );
		$t = preg_replace( "/onsubmit/i"   , "&#111;nsubmit"       , $t );
		$t = preg_replace( "/<body/i"      , "&lt;body"            , $t );
		$t = preg_replace( "/<html/i"      , "&lt;html"            , $t );
		$t = preg_replace( "/document\./i" , "&#100;ocument."      , $t );
		
		return $t;
    }
    
    /*-------------------------------------------------------------------------*/
    // Clean value
    /*-------------------------------------------------------------------------*/
    
    /**
	* UnHTML and stripslashes _GET _POST value
    *
	* @param	string	Input
	* @return	string	Cleaned Input
	* @since	2.1
	*/
    function parse_clean_value($val)
    {
    	if ( $val == "" )
    	{
    		return "";
    	}
    
    	$val = str_replace( "&#032;", " ", $this->txt_stripslashes($val) );
    	
    	if ( isset($this->vars['strip_space_chr']) AND $this->vars['strip_space_chr'] )
    	{
    		$val = str_replace( chr(0xCA), "", $val );  //Remove sneaky spaces
    	}
    	
    	// As cool as this entity is...
    	
    	$val = str_replace( "&#8238;"		, ''			  , $val );
    	
    	$val = str_replace( "&"				, "&amp;"         , $val );
    	$val = str_replace( "<!--"			, "&#60;&#33;--"  , $val );
    	$val = str_replace( "-->"			, "--&#62;"       , $val );
    	$val = preg_replace( "/<script/i"	, "&#60;script"   , $val );
    	$val = str_replace( ">"				, "&gt;"          , $val );
    	$val = str_replace( "<"				, "&lt;"          , $val );
    	$val = str_replace( '"'				, "&quot;"        , $val );
    	$val = str_replace( "\n"			, "<br />"        , $val ); // Convert literal newlines
    	$val = str_replace( "$"				, "&#036;"        , $val );
    	$val = str_replace( "\r"			, ""              , $val ); // Remove literal carriage returns
    	$val = str_replace( "!"				, "&#33;"         , $val );
    	$val = str_replace( "'"				, "&#39;"         , $val ); // IMPORTANT: It helps to increase sql query safety.
    	
    	// Ensure unicode chars are OK
    	
    	if ( $this->allow_unicode )
		{
			$val = preg_replace("/&amp;#([0-9]+);/s", "&#\\1;", $val );
			
			//-----------------------------------------
			// Try and fix up HTML entities with missing ;
			//-----------------------------------------

			$val = preg_replace( "/&#(\d+?)([^\d;])/i", "&#\\1;\\2", $val );
		}
    	
    	return $val;
    }
    
    /**
	* Remove board macros
    *
	* @param	string	Input
	* @return	string	Cleaned Input
	* @since	2.0
	*/
    function remove_tags($text="")
    {
    	// Removes < BOARD TAGS > from posted forms
    	
    	$text = preg_replace( "/(<|&lt;)% (MEMBER BAR|BOARD FOOTER|BOARD HEADER|CSS|JAVASCRIPT|TITLE|BOARD|STATS|GENERATOR|COPYRIGHT|NAVIGATION) %(>|&gt;)/i", "&#60;% \\2 %&#62;", $text );
    	
    	//$text = str_replace( "<%", "&#60;%", $text );
    	
    	return $text;
    }
    
    /**
	* Useless stupid function that serves no purpose other than
	* to create a nice gap between useful functions
    *
	* @param	integer
	* @return	integer
	* @since	2.0
	* @deprecated
	*/
    function is_number($number="")
    {
    	if ($number == "") return -1;
    	
    	if ( preg_match( "/^([0-9]+)$/", $number ) )
    	{
    		return $number;
    	}
    	else
    	{
    		return "";
    	}
    }
    
    /*-------------------------------------------------------------------------*/
    // MEMBER FUNCTIONS             
    /*-------------------------------------------------------------------------*/
    
    /**
	* Set up defaults for a guest user
    *
	* @param	string	Guest name
	* @return	array
	* @since	2.0
	* @todo		Move this into class_session?
	*/
    function set_up_guest($name='Guest')
    {
    	return array( 'name'          		 	=> $name,
    				  'members_display_name' 	=> $name,
    				  '_members_display_name' 	=> $name,
    				  'id'            		 	=> 0,
    				  'password'      		 	=> '',
    				  'email'         		 	=> '',
    				  'title'         		 	=> '',
    				  'mgroup'        		 	=> $this->vars['guest_group'],
    				  'view_sigs'     		 	=> $this->vars['guests_sig'],
    				  'view_img'      		 	=> $this->vars['guests_img'],
    				  'view_avs'     		 	=> $this->vars['guests_ava'],
    				  'member_forum_markers' 	=> array(),
    				  'avatar'				 	=> '',
    				  'member_posts'		 	=> '',
    				  'member_group'		 	=> $this->cache['group_cache'][$this->vars['guest_group']]['g_title'],
    				  'member_rank_img'	 	 	=> '',
    				  'member_joined'		 	=> '',
    				  'member_location'		 	=> '',
    				  'member_number'		 	=> '',
    				  'members_auto_dst'	 	=> 0,
    				  'has_blog'			 	=> 0,
    				  'has_gallery'			 	=> 0,
    				  'is_mod'				 	=> 0,
    				  'last_visit'			 	=> 0,
    				  'login_anonymous'		 	=> '',
    				  'mgroup_others'		 	=> '',
    				  'org_perm_id'			 	=> '',
    				  '_cache'				 	=> array( 'qr_open' => 0 ),
    				  'auto_track'			 	=> 0,
    				  'ignored_users'		 	=> NULL,
    				  'members_editor_choice' 	=> 'std',
					  '_cache'                	=> array( 'friends' => array() )
    				);
    }
    
    /*-------------------------------------------------------------------------*/
    // GET USER AVATAR         
    /*-------------------------------------------------------------------------*/
    
    /**
	* Returns user's avatar
    *
	* @param	string	Member's avatar string
	* @param	integer	Current viewer wants to view avatars
	* @param	string	Avatar dimensions (nxn)
	* @param	string	Avatar type (upload, url)
	* @return	string	HTML
	* @since	2.0
	*/
    function get_avatar($member_avatar="", $member_view_avatars=0, $avatar_dims="x", $avatar_type='', $no_cache=0 )
    {
    	//-----------------------------------------
    	// No avatar?
    	//-----------------------------------------
    	
    	if ( ! $member_avatar or $member_view_avatars == 0 or ! $this->vars['avatars_on'] or ( strpos( $member_avatar, "noavatar" ) AND !strpos( $member_avatar, '.' ) ) )
    	{
    		return "";
    	}
    	
    	if ( substr( $member_avatar, -4 ) == ".swf" and $this->vars['allow_flash'] != 1 )
    	{
    		return "";
    	}
    	
    	//-----------------------------------------
    	// Defaults...
    	//-----------------------------------------
    	
    	$davatar_dims    = explode( "x", strtolower($this->vars['avatar_dims']) );
		$default_a_dims  = explode( "x", strtolower($this->vars['avatar_def']) );
    	$this_dims       = explode( "x", strtolower($avatar_dims) );
		
		if (!isset($this_dims[0])) $this_dims[0] = $davatar_dims[0];
		if (!isset($this_dims[1])) $this_dims[1] = $davatar_dims[1];
		if (!$this_dims[0]) $this_dims[0] = $davatar_dims[0];
		if (!$this_dims[1]) $this_dims[1] = $davatar_dims[1];
		
    	//-----------------------------------------
    	// LEGACY: Determine type
    	//-----------------------------------------
		
		if ( ! $avatar_type )
		{
			if ( preg_match( "/^http:\/\//", $member_avatar ) )
			{
				$avatar_type = 'url';
			}
			else if ( strstr( $member_avatar, "upload:" ) or ( strstr( $member_avatar, 'av-' ) ) )
			{
				$avatar_type   = 'upload';
				$member_avatar = str_replace( 'upload:', '', $member_avatar );
			}
			else
			{
				$avatar_type = 'local';
			}
	 	}
	
		//-----------------------------------------
		// No cache?
		//-----------------------------------------
		
		if ( $no_cache )
		{
			$member_avatar .= '?_time=' . time();
		}
		
		//-----------------------------------------
		// No avatar?
		//-----------------------------------------
		
		if ( $member_avatar == 'noavatar' )
		{
			return '';
		}
		
		//-----------------------------------------
		// URL avatar?
		//-----------------------------------------
		
		else if ( $avatar_type == 'url' )
		{
			if ( substr( $member_avatar, -4 ) == ".swf" )
			{
				return "<object classid=\"clsid:d27cdb6e-ae6d-11cf-96b8-444553540000\" width='{$this_dims[0]}' height='{$this_dims[1]}'>
						<param name='movie' value='{$member_avatar}'><param name='play' value='true'>
						<param name='loop' value='true'><param name='quality' value='high'>
						<param name='wmode' value='transparent'> 
						<embed src='{$member_avatar}' width='{$this_dims[0]}' height='{$this_dims[1]}' play='true' loop='true' quality='high' wmode='transparent'></embed>
						</object>";
			}
			else
			{
				return "<img src='{$member_avatar}' border='0' width='{$this_dims[0]}' height='{$this_dims[1]}' alt='' />";
			}
		}
		
		//-----------------------------------------
		// Not a URL? Is it an uploaded avatar?
		//-----------------------------------------
			
		else if ( ($this->vars['avup_size_max'] > 1) and ( $avatar_type == 'upload' ) )
		{
			$member_avatar = str_replace( 'upload:', '', $member_avatar );
			
			if ( substr( $member_avatar, -4 ) == ".swf" )
			{
				return "<object classid=\"clsid:d27cdb6e-ae6d-11cf-96b8-444553540000\" width='{$this_dims[0]}' height='{$this_dims[1]}'>
						<param name='movie' value='{$this->vars['upload_url']}/$member_avatar'><param name='play' value='true'>
						<param name='loop' value='true'><param name='quality' value='high'>
						<param name='wmode' value='transparent'> 
					    <embed src='{$this->vars['upload_url']}/$member_avatar' width='{$this_dims[0]}' height='{$this_dims[1]}' play='true' loop='true' quality='high' wmode='transparent'></embed>
						</object>";
			}
			else
			{
				return "<img src='{$this->vars['upload_url']}/$member_avatar' border='0' width='{$this_dims[0]}' height='{$this_dims[1]}' alt='' />";
			}
		}
		
		//-----------------------------------------
		// No, it's not a URL or an upload, must
		// be a normal avatar then
		//-----------------------------------------
		
		else if ($member_avatar != "")
		{
			//-----------------------------------------
			// Do we have an avatar still ?
		   	//-----------------------------------------
		   	
			return "<img src='{$this->vars['AVATARS_URL']}/{$member_avatar}' border='0' alt='' />";
		}
		else
		{
			//-----------------------------------------
			// No, ok - return blank
			//-----------------------------------------
			
			return "";
		}
    }

	/*-------------------------------------------------------------------------*/
 	// Set up member data
 	/*-------------------------------------------------------------------------*/
 	
 	/**
 	* Sets the personal data
 	*
 	* @return	void
 	* @since	IPB 2.2.0.2006-7-31
 	*/
 	function member_set_information( $member, $noids=0, $use_parsed=1 )
 	{
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! is_array( $member ) or ! count( $member ) )
		{
			return $member;
		}
		
		if ( $use_parsed )
		{
			if ( array_key_exists( $member['id'], $this->parsed_members ) )
			{
				return $this->parsed_members[ $member['id'] ];
			}
		}
	
		//-----------------------------------------
		// Check URL
		//-----------------------------------------
		
		$this->vars['img_url'] = ( ! $this->vars['img_url'] ) ?  $this->vars['board_url'] . '/style_images/' . $this->skin['_imagedir'] : $this->vars['img_url'];
		
		//-----------------------------------------
		// Main photo
		//-----------------------------------------
		
		if ( ! $member['pp_main_photo'] OR !$this->member['g_mem_info'] )
		{
			$member['pp_main_photo']  = $this->vars['img_url'].'/folder_profile_portal/pp-blank-large.png';;
			$member['pp_main_width']  = 150;
			$member['pp_main_height'] = 150;
			$member['_has_photo']     = 0;
		}
		else
		{
			$member['pp_main_photo'] = $this->vars['upload_url'] . '/' . $member['pp_main_photo'];
			$member['_has_photo']    = 1;
		}
		
		//-----------------------------------------
		// Thumbie
		//-----------------------------------------
		
		if ( ! $member['pp_thumb_photo'] )
		{
			if( $member['_has_photo'] )
			{
				$member['pp_thumb_photo']  = $member['pp_main_photo'];
			}
			else
			{
				$member['pp_thumb_photo']  = $this->vars['img_url'].'/folder_profile_portal/pp-blank-thumb.png';
			}
			
			$member['pp_thumb_width']  = 50;
			$member['pp_thumb_height'] = 50;
		}
		else
		{
			$member['pp_thumb_photo'] = $this->vars['upload_url'] . '/' . $member['pp_thumb_photo'];
		}
		
		//-----------------------------------------
		// Mini
		//-----------------------------------------
		
		$_data = $this->scale_image( array( 'max_height' => 25, 'max_width' => 25, 'cur_width' => $member['pp_thumb_width'], 'cur_height' => $member['pp_thumb_height'] ) );
		
		$member['pp_mini_photo']  = $member['pp_thumb_photo'];
		$member['pp_mini_width']  = $_data['img_width'];
		$member['pp_mini_height'] = $_data['img_height'];
		
		//-----------------------------------------
		// Gender...
		//-----------------------------------------
		
		if ( IPB_THIS_SCRIPT != 'admin' )
		{
			$member['_pp_gender_image'] = $noids ? $this->compiled_templates['skin_global']->personal_portal_gender_image( $member, 1 ) : $this->compiled_templates['skin_global']->personal_portal_gender_image( $member );
		}
		
		$member['_pp_gender_text']  = $member['pp_gender'] == 'male' ? $this->lang['js_gender_male'] : ( $member['pp_gender'] == 'female' ? $this->lang['js_gender_female'] : $this->lang['js_gender_mystery'] );
		
		//-----------------------------------------
		// Online?
		//-----------------------------------------
		
		$time_limit = time() - $this->vars['au_cutoff'] * 60;
	
		$member['_online'] = 0;
	
		list( $be_anon, $loggedin ) = explode( '&', $member['login_anonymous'] );
		
		if ( ( $member['last_visit'] > $time_limit or $member['last_activity'] > $time_limit ) AND $be_anon != 1 AND $loggedin == 1 )
		{
			$member['_online'] = 1;
		}

		if ( IPB_THIS_SCRIPT != 'admin' )
		{
			$member['_pp_online_image'] = $this->compiled_templates['skin_global']->personal_portal_online_image( $member );
		}

		//-----------------------------------------
		// Last Active
		//-----------------------------------------
		
		$member['_last_active'] = $this->get_date( $member['last_activity'], 'SHORT' );
		
		if( $member['login_anonymous']{0} == '1' )
		{
			// Member last logged in anonymous
			
			if( $this->member['mgroup'] != $this->vars['admin_group'] OR $this->vars['disable_admin_anon'] )
			{
				$member['_last_active'] = $this->lang['private'];
			}
		}		
		
		//-----------------------------------------
		// Rating
		//-----------------------------------------
		
		$member['_pp_rating_real'] = intval( $member['pp_rating_real'] );
		
		//-----------------------------------------
		// Long display names
		//-----------------------------------------
		
		$member['members_display_name_short'] = $this->txt_truncate( $member['members_display_name'], 16 );
		
		//-----------------------------------------
		// Other stuff not worthy of individual comments
		//-----------------------------------------
		
		$member['members_profile_views'] = isset($member['members_profile_views']) ? $member['members_profile_views'] : 0;
		
		$member['_pp_profile_views'] = $this->do_number_format( $member['members_profile_views'] );
		
		$member['icq_number']		 = $member['icq_number'] > 0 ? $member['icq_number'] : '';
		
		//-----------------------------------------
		// Bye.
		//-----------------------------------------
		
		$this->parsed_members[ $member['id'] ] = $member;
		
		return $member;
	}
 	
 	/*-------------------------------------------------------------------------*/
 	// Quick, INIT? a.k.a Just enough information to perform
 	// (Sorry, listening to stereophonics still)
 	/*-------------------------------------------------------------------------*/
 	
 	/**
	* Very quick init, if index.php has been passed on hasn't been loaded
    *
	* @return	void
	* @since	2.0
	*/
 	function quick_init()
 	{
 		$this->load_skin();
    	    
	   //-----------------------------------------
	   // Grab session cookie
	   //-----------------------------------------
			  
	   $this->session_id = $this->sess->session_id ? $this->sess->session_id : $this->my_getcookie('session_id');
	   
	   //-----------------------------------------
	   // Organize default info
	   //-----------------------------------------
	   
	   $this->base_url   	= $this->vars['board_url'].'/index.'.$this->vars['php_ext'].'?s='.$this->session_id;
	   $this->js_base_url 	= $this->vars['board_url'].'/index.'.$this->vars['php_ext'].'?s='.$this->session_id;
	   $this->skin_rid   	= $this->skin['set_id'];
	   $this->skin_id    	= 's'.$this->skin['set_id'];
	   
	   if ($this->vars['default_language'] == "")
	   {
		   $this->vars['default_language'] = 'en';
	   }
	   
	   $this->lang_id = $this->member['language'] ? $this->member['language'] : $this->vars['default_language'];
	   
	   if ( ($this->lang_id != $this->vars['default_language']) and (! is_dir( CACHE_PATH."cache/lang_cache/".$this->lang_id ) ) )
	   {
		   $this->lang_id = $this->vars['default_language'];
	   }
	   
	   //-----------------------------------------
	   // Get words & skin
	   //-----------------------------------------
	   
	   $this->load_language("lang_global");
	   
	   $this->vars['img_url'] = 'style_images/' . $this->skin['_imagedir'];
	   
	   if ( ! $this->compiled_templates['skin_global'] )
	   {
		   $this->load_template('skin_global');
	   }
 	}
 
    /*-------------------------------------------------------------------------*/
    // ERROR FUNCTIONS             
    /*-------------------------------------------------------------------------*/
    
    /**
	* Show error message
    *
    * @param	array	'LEVEL', 'INIT', 'MSG', 'EXTRA'
	* @return	void
	* @since	2.0
	*/
    function Error($error)
    {
    	$override = 0;
    	
    	//-----------------------------------------
    	// Showing XML / AJAX functions?
    	//-----------------------------------------
    	
    	if ( $this->input['act'] == 'xmlout' )
    	{
    		@header( "Content-type: text/plain" );
			print 'error';
			exit();
		}
    	
    	//-----------------------------------------
    	// Initialize if not done so yet
    	//-----------------------------------------
    	
    	if ( isset($error['INIT']) AND $error['INIT'] == 1)
    	{
    		$this->quick_init();
		}
		else
		{
			$this->session_id = $this->my_session;
		}
		
		if ( !isset($this->compiled_templates['skin_global']) OR !is_object( $this->compiled_templates['skin_global'] ) )
		{
			$this->load_template('skin_global');
		}
		
		//-----------------------------------------
		// Get error words
		//-----------------------------------------
		
    	$this->load_language('lang_error');
    	
    	list($em_1, $em_2) = explode( '@', $this->vars['email_in'] );
    	
    	$msg = $this->lang[ $error['MSG'] ];
    	
    	//-----------------------------------------
    	// Extra info?
    	//-----------------------------------------
    	
    	if ( isset($error['EXTRA']) AND $error['EXTRA'] )
    	{
    		$msg = str_replace( '<#EXTRA#>', $error['EXTRA'], $msg );
    	}
    	
    	//-----------------------------------------
    	// Show error
    	//-----------------------------------------
    	
    	$html = $this->compiled_templates['skin_global']->Error( $msg, $em_1, $em_2, 1);
    	
    	//-----------------------------------------
    	// If we're a guest, show the log in box..
    	//-----------------------------------------
    	
    	if ($this->member['id'] == "" and $error['MSG'] != 'server_too_busy' and $error['MSG'] != 'account_susp')
    	{
    		$safe_string = $this->base_url . str_replace( '&amp;', '&', $this->parse_clean_value($this->my_getenv('QUERY_STRING')) );
    		
    		$html = str_replace( "<!--IBF.LOG_IN_TABLE-->", $this->compiled_templates['skin_global']->error_log_in( str_replace( '&', '&amp;', $safe_string ) ), $html);
    		$override = 1;
    	}
    	
    	//-----------------------------------------
    	// Do we have any post data to keepy?
    	//-----------------------------------------
    	
    	if ( $this->input['act'] == 'Post' OR $this->input['act'] == 'Msg' OR $this->input['act'] == 'calendar' )
    	{
    		if ( $_POST['Post'] )
    		{
    			$post_thing = $this->compiled_templates['skin_global']->error_post_textarea($this->txt_htmlspecialchars($this->txt_stripslashes($_POST['Post'])) );
    			
    			$html = str_replace( "<!--IBF.POST_TEXTAREA-->", $post_thing, $html );
    		}
    	}
    	
    	//-----------------------------------------
    	// Update session
    	//-----------------------------------------
    	
    	$this->DB->do_shutdown_update( 'sessions', array( 'in_error' => 1 ), "id='{$this->my_session}'" );
    	
    	//-----------------------------------------
    	// Print
    	//-----------------------------------------
    	
    	$print           =  new display();
    	$print->ipsclass =& $this;
    	
    	$print->add_output($html);
    		
    	$print->do_output( array( 'OVERRIDE' => $override, 'TITLE' => $this->lang['error_title'] ) );
    }
    
    /*-------------------------------------------------------------------------*/
    // Show Board Offline
    /*-------------------------------------------------------------------------*/
    
    /**
	* Show board offline message
    *
	* @return	void
	* @since	2.0
	*/
    function board_offline()
    {
    	$this->quick_init();
    	
    	//-----------------------------------------
    	// Get offline message (not cached)
    	//-----------------------------------------
    	
    	$row = $this->DB->simple_exec_query( array( 'select' => '*', 'from' => 'conf_settings', 'where' => "conf_key='offline_msg'" ) );
    	
    	$this->load_language("lang_error");
    	
    	$msg = preg_replace( "/\n/", "<br />", stripslashes( $row['conf_value'] ) );
    	
    	$html = $this->compiled_templates['skin_global']->board_offline( $msg );
    	
    	$print           = new display();
    	$print->ipsclass =& $this;
    	$print->add_output($html);
    		
    	$print->do_output( array(
								   'OVERRIDE'   => 1,
								   'TITLE'      => $this->lang['offline_title'],
								)
						 );
    }
    								
    /*-------------------------------------------------------------------------*/
    // Variable chooser             
    /*-------------------------------------------------------------------------*/
    
    /**
	* Choose a variable (silly function)
    *
    * @param	array Mixed variables
	* @return	mixed
	* @since	2.0
	*/
    function select_var($array)
    {
    	if ( !is_array($array) ) return -1;
    	
    	ksort($array);
    	
    	$chosen = -1;  // Ensure that we return zero if nothing else is available
    	
    	foreach ($array as $v)
    	{
    		if ( isset($v) )
    		{
    			$chosen = $v;
    			break;
    		}
    	}
    	
    	return $chosen;
    }
    
	/*-------------------------------------------------------------------------*/
	// Array filter: Clean read topics
	/*-------------------------------------------------------------------------*/
    
    /**
	* Array sort Used to remove out of date topic marking entries
    *
    * @param	mixed
	* @return	mixed
	* @since	2.0
	*/
    function array_filter_clean_read_topics ( $var )
	{
		global $ipsclass;
		
		return $var > $ipsclass->vars['db_topic_read_cutoff'];
	}
    
	/*-------------------------------------------------------------------------*/
	// Memory debug, make flag
	/*-------------------------------------------------------------------------*/
	
	function memory_debug_make_flag()
	{
		if ( IPS_MEMORY_DEBUG_MODE AND function_exists( 'memory_get_usage' ) )
		{
			return memory_get_usage();
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Memory debug, make flag
	/*-------------------------------------------------------------------------*/
	
	function memory_debug_add( $comment, $init_usage=0 )
	{
		if ( IPS_MEMORY_DEBUG_MODE AND function_exists( 'memory_get_usage' ) )
		{
			$_END  = memory_get_usage();
			$_USED = $_END - $init_usage;
			$this->_memory_debug[] = array( $comment, $_USED );
		}
	}
	
    /*-------------------------------------------------------------------------*/
    // LEGACY MODE STUFF
    /*-------------------------------------------------------------------------*/
    
    
	/*-------------------------------------------------------------------------*/
	// Require, parse and return an array containing the language stuff                 
	/*-------------------------------------------------------------------------*/ 
	
	/**
	* LEGACY MODE: load_words
    *
    * @param	array	Current language array
    * @param	string	File name
    * @param	string	Cache directory
	* @return	mixed
	* @deprecated	Since 2.1
	* @since	1.0
	*/
	
	function load_words($current_lang_array, $area, $lang_type)
	{
		require ROOT_PATH."cache/lang_cache/".$lang_type."/".$area.".php";
		
		foreach ($lang as $k => $v)
		{
			$current_lang_array[$k] = stripslashes($v);
		}
		
		unset($lang);
		
		return $current_lang_array;
	}
	
	/*-------------------------------------------------------------------------*/
	// Redirect: parse_clean_value       
	/*-------------------------------------------------------------------------*/
	
	/**
	* LEGACY MODE: clean_value (alias of parse_clean_value)
    *
    * @param	string
	* @return	string
	* @deprecated	Since 2.1
	* @since	1.0
	* @see		parse_clean_value
	*/
	function clean_value( $t )
	{
		return $this->parse_clean_value( $t );
	}
	
	
	function parse_member( $member=array(), $custom_fields=1, $skin_file='skin_topic' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$group_name                = $this->make_name_formatted( $this->cache['group_cache'][ $member['mgroup'] ]['g_title'], $member['mgroup'] );
		$pips                      = 0;
		$member['member_rank_img'] = "";
		
		//-----------------------------------------
		// Avatar
		//-----------------------------------------
		
		$member['avatar'] = $this->get_avatar( $member['avatar_location'], $this->member['view_avs'], $member['avatar_size'], $member['avatar_type'] );
		
		//-----------------------------------------
		// Ranks
		//-----------------------------------------

		foreach($this->cache['ranks'] as $k => $v)
		{
			if ($member['posts'] >= $v['POSTS'])
			{
				if (!$member['title'])
				{
					$member['title'] = $this->cache['ranks'][ $k ]['TITLE'];
				}
				
				$pips = $v['PIPS'];
				break;
			}
		}
		
		//-----------------------------------------
		// Group image
		//-----------------------------------------
		
		if ( $this->cache['group_cache'][ $member['mgroup'] ]['g_icon'] )
		{
			$member['member_rank_img'] = $this->compiled_templates[ $skin_file ]->member_rank_img($this->cache['group_cache'][ $member['mgroup'] ]['g_icon']);
		}
		else if ( $pips )
		{
			if ( is_numeric( $pips ) )
			{
				for ($i = 1; $i <= $pips; ++$i)
				{
					$member['member_rank_img'] .= "<{A_STAR}>";
				}
			}
			else
			{
				$member['member_rank_img'] = $this->compiled_templates[ $skin_file ]->member_rank_img( 'style_images/<#IMG_DIR#>/folder_team_icons/'.$pips );
			}
		}
		
		$member['member_joined']   = $this->compiled_templates[ $skin_file ]->member_joined( $this->get_date( $member['joined'], 'JOINED' ) );
		$member['member_group']    = $this->compiled_templates[ $skin_file ]->member_group( $group_name );
		$member['member_posts']    = $this->compiled_templates[ $skin_file ]->member_posts( $this->do_number_format( intval( $member['posts'] ) ) );
		$member['member_number']   = $this->compiled_templates[ $skin_file ]->member_number( $this->do_number_format($member['id']) );
		$member['profile_icon']    = $this->compiled_templates[ $skin_file ]->member_icon_profile( $member['id'] );
		$member['message_icon']    = $this->compiled_templates[ $skin_file ]->member_icon_msg( $member['id'] );
		$member['member_location'] = $member['location'] ? $this->compiled_templates[ $skin_file ]->member_location( $member['location'] ) : '';
		$member['email_icon']      = ! $member['hide_email'] ? $this->compiled_templates[ $skin_file ]->member_icon_email( $member['id'] ) : '';
		$member['addresscard']     = $member['id'] ? $this->compiled_templates[ $skin_file ]->member_icon_vcard( $member['id'] ) : '';
		
		//-----------------------------------------
		// Warny porny?
		//-----------------------------------------
		
		$member['warn_percent']	= NULL;
		$member['warn_img']		= NULL;
		$member['warn_text']	= NULL;
		$member['warn_add']		= NULL;
		$member['warn_minus']	= NULL;
		
		if ( $this->vars['warn_on'] and ( ! strstr( ','.$this->vars['warn_protected'].',', ','.$member['mgroup'].',' ) ) )
		{
			if ( ( isset($this->member['_moderator'][ $this->topic['forum_id'] ]['allow_warn'])
				AND $this->member['_moderator'][ $this->topic['forum_id'] ]['allow_warn'] )
				OR ( $this->member['g_is_supmod'] == 1 )
				OR ( $this->vars['warn_show_own'] and ( $this->member['id'] == $member['id'] ) ) 
			   )
			{
				// Work out which image to show.
				
				if ( ! $this->vars['warn_show_rating'] )
				{
					if ( $member['warn_level'] <= $this->vars['warn_min'] )
					{
						$member['warn_img']     = '<{WARN_0}>';
						$member['warn_percent'] = 0;
					}
					else if ( $member['warn_level'] >= $this->vars['warn_max'] )
					{
						$member['warn_img']     = '<{WARN_5}>';
						$member['warn_percent'] = 100;
					}
					else
					{
						
						$member['warn_percent'] = $member['warn_level'] ? sprintf( "%.0f", ( ($member['warn_level'] / $this->vars['warn_max']) * 100) ) : 0;
						
						if ( $member['warn_percent'] > 100 )
						{
							$member['warn_percent'] = 100;
						}
						
						if ( $member['warn_percent'] >= 81 )
						{
							$member['warn_img'] = '<{WARN_5}>';
						}
						else if ( $member['warn_percent'] >= 61 )
						{
							$member['warn_img'] = '<{WARN_4}>';
						}
						else if ( $member['warn_percent'] >= 41 )
						{
							$member['warn_img'] = '<{WARN_3}>';
						}
						else if ( $member['warn_percent'] >= 21 )
						{
							$member['warn_img'] = '<{WARN_2}>';
						}
						else if ( $member['warn_percent'] >= 1 )
						{
							$member['warn_img'] = '<{WARN_1}>';
						}
						else
						{
							$member['warn_img'] = '<{WARN_0}>';
						}
					}
					
					if ( $member['warn_percent'] < 1 )
					{
						$member['warn_percent'] = 0;
					}
					
					$member['warn_text']  = $this->compiled_templates[ $skin_file ]->warn_level_warn($member['id'], $member['warn_percent'] );
				}
				else
				{
					// Ratings mode..
					
					$member['warn_text']  = $this->lang['tt_rating'];
					$member['warn_img']   = $this->compiled_templates[ $skin_file ]->warn_level_rating($member['id'], $member['warn_level'], $this->vars['warn_min'], $this->vars['warn_max']);
				}
								
				if ( ( isset($this->member['_moderator'][ $this->topic['forum_id'] ]['allow_warn']) AND $this->member['_moderator'][ $this->topic['forum_id'] ]['allow_warn'] ) or $this->member['g_is_supmod'] == 1 )
				{
					$member['warn_add']   = "<a href='{$this->base_url}act=warn&amp;type=add&amp;mid={$member['id']}&amp;t={$this->topic['tid']}&amp;st=".intval($this->input['st'])."' title='{$this->lang['tt_warn_add']}'><{WARN_ADD}></a>";
					$member['warn_minus'] = "<a href='{$this->base_url}act=warn&amp;type=minus&amp;mid={$member['id']}&amp;t={$this->topic['tid']}&amp;st=".intval($this->input['st'])."' title='{$this->lang['tt_warn_minus']}'><{WARN_MINUS}></a>";
				}
			}
		}
		
		//-----------------------------------------
		// Profile fields stuff
		//-----------------------------------------
		
		$member['custom_fields'] = "";
		
		if ( $this->vars['custom_profile_topic'] == 1 AND $custom_fields == 1 )
		{
			if( !is_object( $this->custom_fields ) )
			{
				require_once( ROOT_PATH.'sources/classes/class_custom_fields.php' );
				$this->custom_fields = new custom_fields( $this->DB );
				
				$this->custom_fields->member_id  = $this->member['id'];
				$this->custom_fields->cache_data = $this->cache['profilefields'];
				$this->custom_fields->admin      = intval($this->member['g_access_cp']);
				$this->custom_fields->supmod     = intval($this->member['g_is_supmod']);
			}
			
			if ( $this->custom_fields )
			{
				$this->custom_fields->member_data = $member;
		    	$this->custom_fields->admin        = intval($this->member['g_access_cp']);
		    	$this->custom_fields->supmod       = intval($this->member['g_is_supmod']);
		    	$this->custom_fields->member_id	   = $this->member['id'];
				$this->custom_fields->init_data();
				$this->custom_fields->parse_to_view( 1 );
				
				if ( count( $this->custom_fields->out_fields ) )
				{
					foreach( $this->custom_fields->out_fields as $i => $data )
					{
						if ( $data )
						{
							$member['custom_fields'] .= "\n".$this->custom_fields->method_format_field_for_topic_view( $i );
						}
					}
				}
			}
		}
		
		//-----------------------------------------
		// Photo and such
		//-----------------------------------------
		
		$member = $this->member_set_information( $member );
		
		return $member;
	}	
	
	
    
      
} // end class

?>