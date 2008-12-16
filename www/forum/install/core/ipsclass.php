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
|   Time: Tue, 01 Nov 2005 14:38:09 GMT
|   Release: 68ff9281c6fdcce88910e08cec1045a3
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
|   > $Date: 2005-10-10 16:01:09 +0100 (Mon, 10 Oct 2005) $
|   > $Revision: 5 $
|   > $Author: josh $
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
	var $version      = "v2.1.2";
	
	/**
	* LONG version number (Eg: 21000)
	*
	* @var string
	*/
	var $acpversion   = '21008';
	
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
	var $input              = array();
	
	/**
	* Setting variables array
	*
	* @var array
	*/
	var $vars               = array();
	
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
	var $lastclick          = "";
	var $location           = "";
	var $debug_html         = "";
	var $perm_id            = "";
	var $offset             = "";
	var $num_format         = "";
	var $query_string_safe  = '';
	
	/**
	* MD5 Check variable
	*/
	var $md5_check          = '';
	/**#@-*/
	
	var $server_load        = 0;
	var $can_use_fancy_js   = 0;
	var $offset_set         = 0;
	var $allow_unicode      = 1;
	var $get_magic_quotes   = 0;
	
	/**#@+
	* @access public
	* @var object 
	*/
	var $converge;
	var $print;
	var $sess;
	var $forums;
	/**#@-*/

	/**#@+
	* @access public
	* @var array 
	*/
	var $topic_cache      = array();
	var $time_formats     = array();
	var $time_options     = array();
	var $today_array      = array();
	
	/**
	* User's browser array (version, browser)
	*/
	var $browser;
	/**#@-*/
	
	
	
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
		$this->time_options = array( 'JOINED' => $this->vars['clock_joined'],
									 'SHORT'  => $this->vars['clock_short'],
									 'LONG'   => $this->vars['clock_long'],
									 'TINY'   => $this->vars['clock_tiny'] ? $this->vars['clock_tiny'] : 'j M Y - G:i',
									 'DATE'   => $this->vars['clock_date'] ? $this->vars['clock_date'] : 'j M Y',
								   );
								   
		$this->num_format = ($this->vars['number_format'] == 'space') ? ' ' : $this->vars['number_format'];
		
		$this->get_magic_quotes = get_magic_quotes_gpc();
		
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
		 
		if ( ! $this->ip_address OR $this->ip_address == '...' )
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
		$this->query_string_real = str_replace( '&amp;' , '&', $this->query_string_safe );
		
		//-----------------------------------------
		//  Upload dir?
		//-----------------------------------------
		
		$this->vars['upload_dir'] = $this->vars['upload_dir'] ? $this->vars['upload_dir'] : INS_ROOT_PATH.'uploads';
		
		//-----------------------------------------
		// Char set
		//-----------------------------------------
		
		$this->vars['gb_char_set'] = $this->vars['gb_char_set'] ? $this->vars['gb_char_set'] : 'iso-8859-1';
		
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
		// Generate cache list
		//--------------------------------
		
		$cachelist = "'".implode( "','", $cachearray )."'";
		
		//--------------------------------
		// Get...
		//--------------------------------
		
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
				
				unset( $tmp );
			}
			else
			{
				if ( $r['cs_array'] )
				{
					$this->cache[ $r['cs_key'] ] = unserialize( stripslashes($r['cs_value']) );
				}
				else
				{
					$this->cache[ $r['cs_key'] ] = $r['cs_value'];
				}
			}
		}
		
		if ( ! isset( $this->cache['systemvars'] ) )
		{
			$this->DB->simple_exec_query( array( 'delete' => 'cache_store', 'where' => "cs_key='systemvars'" ) );
			$this->DB->do_insert( 'cache_store', array( 'cs_key' => 'systemvars', 'cs_value' => addslashes(serialize(array())), 'cs_array' => 1 ) );
		}
		
		//--------------------------------
		// Set up cache path
		//--------------------------------
		
		if ( $this->vars['ipb_cache_path'] )
		{
			define( 'CACHE_PATH', $this->vars['ipb_cache_path'] );
		}
		else
		{
			define( 'CACHE_PATH', INS_ROOT_PATH . "../" );
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
	}
	
	/*-------------------------------------------------------------------------*/
	// INIT: DB Connection
	/*-------------------------------------------------------------------------*/
	
	/**
	* Initializes the database connection and populates $ipsclass->DB
	*
	* @return void
	*/
	
	function init_db_connection( $db, $user, $pass, $host, $pre, $driver='mysql' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$file = '';
		
		//-----------------------------------------
		// Cache file?
		//-----------------------------------------
		
		if ( defined( 'INS_SQL_FILE' ) )
		{
			$file = str_replace( '<%driver%>', $driver, INS_SQL_FILE );
		}
		
		$classname = "db_driver_".$driver;
		
		$this->DB = new $classname;
		
		$this->DB->obj['sql_database']     = $db;
		$this->DB->obj['sql_user']         = $user;
		$this->DB->obj['sql_pass']         = $pass;
		$this->DB->obj['sql_host']         = $host;
		$this->DB->obj['sql_tbl_prefix']   = $pre;
		$this->DB->obj['use_shutdown']     = 0;
		
		if ( $file )
		{
			$this->DB->obj['query_cache_file'] = $file;
		}

		if ( ! defined( 'SQL_PREFIX' ) )
		{
			define( 'SQL_PREFIX', $pre );
		}
	
		//-----------------------------------
		// Required vars?
		//-----------------------------------
		
		if ( is_array( $this->DB->connect_vars ) and count( $this->DB->connect_vars ) )
		{
			foreach( $this->DB->connect_vars as $k => $v )
			{
				$this->DB->connect_vars[ $k ] = $this->vars[ $k ];
			}
		}
		
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
			
			$current_cache_array = unserialize( $member['members_cache'] );
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
		
		if ( $this->vars['forum_cache_minimum'] )
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
			
			$fr['read_perms']   = $perms['read_perms'];
			$fr['reply_perms']  = $perms['reply_perms'];
			$fr['start_perms']  = $perms['start_perms'];
			$fr['upload_perms'] = $perms['upload_perms'];
			$fr['show_perms']   = $perms['show_perms'];
			
			unset($fr['permission_array']);
			
			$this->cache['forum_cache'][ $fr['id'] ] = $fr;
		}
		
		$this->update_cache( array( 'name' => 'forum_cache', 'array' => 1, 'deletefirst' => 1, 'donow' => 1 ) );
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
		
		if ( $v['name'] == 'forum_cache' AND $this->vars['no_cache_forums'] )
		{ 
			return;
		}
		
		//-----------------------------------------
		// Next...
		//-----------------------------------------
		
		if ( $v['name'] )
		{
			if ( ! $v['value'] )
			{
				$value = $this->DB->add_slashes(serialize($this->cache[ $v['name'] ]));
			}
			
			$this->DB->manual_addslashes          = 1;
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
			
			$this->DB->manual_addslashes = 0;
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
		
		//-----------------------------------------
		// Process mail queue
		//-----------------------------------------
			
		$this->process_mail_queue();
		
		$this->DB->close_db();
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
	* @return	string	Parsed string
	* @since	2.1
	*/
	function txt_alphanumerical_clean( $t )
	{
		return preg_replace( "/[^a-zA-Z0-9\-\_]/", "" , $t );
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
	* Create a random 8 character password
	*
	* @todo		check if we still need this?
	* @return	string	Password
	* @since	2.0
	*/
	function make_password()
	{
		$pass = "";
		$chars = array(
			"1","2","3","4","5","6","7","8","9","0",
			"a","A","b","B","c","C","d","D","e","E","f","F","g","G","h","H","i","I","j","J",
			"k","K","l","L","m","M","n","N","o","O","p","P","q","Q","r","R","s","S","t","T",
			"u","U","v","V","w","W","x","X","y","Y","z","Z");
	
		$count = count($chars) - 1;
	
		srand((double)microtime()*1000000);

		for($i = 0; $i < 8; $i++)
		{
			$pass .= $chars[rand(0, $count)];
		}
	
		return($pass);
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
    	
    	$r = ( ($this->member['time_offset'] != "") ? $this->member['time_offset'] : $this->vars['time_offset'] ) * 3600;
			
		if ( $this->vars['time_adjust'] )
		{
			$r += ($this->vars['time_adjust'] * 60);
		}
		
		if ( $this->member['dst_in_use'] )
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
    	$tmp = gmdate( 'j,n,Y,G,i,s,w,z,l,F', $gmt_stamp );
    	
    	list( $day, $month, $year, $hour, $min, $seconds, $wday, $yday, $weekday, $fmon ) = explode( ',', $tmp );
    	
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
	    
	    if( is_array( $_SERVER ) AND count( $_SERVER ) )
	    {
		    if( isset( $_SERVER[$key] ) )
		    {
			    $return = $_SERVER[$key];
		    }
	    }
	    
	    if( !$return )
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
    function my_setcookie($name, $value = "", $sticky = 1)
    {
        if ( $this->no_print_header )
        {
        	return;
        }
        
        if ($sticky == 1)
        {
        	$expires = time() + 60*60*24*365;
        }

        $this->vars['cookie_domain'] = $this->vars['cookie_domain'] == "" ? ""  : $this->vars['cookie_domain'];
        $this->vars['cookie_path']   = $this->vars['cookie_path']   == "" ? "/" : $this->vars['cookie_path'];
        
        $name = $this->vars['cookie_id'].$name;
      
        @setcookie($name, $value, $expires, $this->vars['cookie_path'], $this->vars['cookie_domain']);
    }
    
    /*-------------------------------------------------------------------------*/
    // Cookies, cookies everywhere and not a byte to eat.      forum_read          
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
    	# THIS NEEDS TO BE HERE!
    	$this->get_magic_quotes = @get_magic_quotes_gpc();
    	
		if ( is_array($_GET) )
		{
			while( list($k, $v) = each($_GET) )
			{
				if ( is_array($_GET[$k]) )
				{
					while( list($k2, $v2) = each($_GET[$k]) )
					{
						$this->input[ $this->parse_clean_key($k) ][ $this->parse_clean_key($k2) ] = $this->parse_clean_value($v2);
					}
				}
				else
				{
					$this->input[ $this->parse_clean_key($k) ] = $this->parse_clean_value($v);
				}
			}
		}
		
		//----------------------------------------
		// Overwrite GET data with post data
		//----------------------------------------
		
		if ( is_array($_POST) )
		{
			while( list($k, $v) = each($_POST) )
			{
				if ( is_array($_POST[$k]) )
				{
					while( list($k2, $v2) = each($_POST[$k]) )
					{
						$this->input[ $this->parse_clean_key($k) ][ $this->parse_clean_key($k2) ] = $this->parse_clean_value($v2);
					}
				}
				else
				{
					$this->input[ $this->parse_clean_key($k) ] = $this->parse_clean_value($v);
				}
			}
		}
		
		$this->input['request_method'] = strtolower($this->my_getenv('REQUEST_METHOD'));
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
    	$key = preg_replace( "/\.\./"           , ""  , $key );
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
    	if ($val == "")
    	{
    		return "";
    	}
    
    	$val = str_replace( "&#032;", " ", $val );
    	
    	if ( $this->vars['strip_space_chr'] )
    	{
    		$val = str_replace( chr(0xCA), "", $val );  //Remove sneaky spaces
    	}
    	
    	$val = str_replace( "&"            , "&amp;"         , $val );
    	$val = str_replace( "<!--"         , "&#60;&#33;--"  , $val );
    	$val = str_replace( "-->"          , "--&#62;"       , $val );
    	$val = preg_replace( "/<script/i"  , "&#60;script"   , $val );
    	$val = str_replace( ">"            , "&gt;"          , $val );
    	$val = str_replace( "<"            , "&lt;"          , $val );
    	$val = str_replace( "\""           , "&quot;"        , $val );
    	$val = preg_replace( "/\n/"        , "<br />"        , $val ); // Convert literal newlines
    	$val = preg_replace( "/\\\$/"      , "&#036;"        , $val );
    	$val = preg_replace( "/\r/"        , ""              , $val ); // Remove literal carriage returns
    	$val = str_replace( "!"            , "&#33;"         , $val );
    	$val = str_replace( "'"            , "&#39;"         , $val ); // IMPORTANT: It helps to increase sql query safety.
    	
    	// Ensure unicode chars are OK
    	
    	if ( $this->allow_unicode )
		{
			$val = preg_replace("/&amp;#([0-9]+);/s", "&#\\1;", $val );
		}
		
		// Strip slashes if not already done so.
		
    	if ( $this->get_magic_quotes )
    	{
    		$val = stripslashes($val);
    	}
    	
    	// Swap user entered backslashes
    	
    	$val = preg_replace( "/\\\(?!&amp;#|\?#)/", "&#092;", $val ); 
    	
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
      
} // end class

?>