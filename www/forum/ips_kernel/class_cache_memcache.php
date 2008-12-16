<?php
/*
+--------------------------------------------------------------------------
|   Invision Power Services Kernel [Cache Abstraction]
|	Invision Power Board
|   =============================================
|   by Matthew Mecham
|   (c) 2001 - 2006 Invision Power Services, Inc.
|   http://www.invisionpower.com
|   =============================================
|   Web: http://www.invisionboard.com
+---------------------------------------------------------------------------
|   THIS IS NOT FREE / OPEN SOURCE SOFTWARE
+---------------------------------------------------------------------------
|
|   > Core Module
|   > Module written by Brandon Farber
|   > Date started: Friday 19th May 2006 17:33 
|
|	> Module Version Number: 1.0
+--------------------------------------------------------------------------
*/

/**
* IPS Kernel Pages: Cache Object Core
*	-- Memcache Cache Storage
*
* Basic Usage Examples
* <code>
* $cache = new cache_lib( 'identifier' );
* Update:
* $db->do_put( 'key', 'value' [, 'ttl'] );
* Remove
* $db->do_remove( 'key' );
* Retrieve
* $db->do_get( 'key' );
* </code>
*  		
* @package		IPS_KERNEL
* @subpackage	Cache Abstraction
* @author   	Brandon Mecham
* @version		1.0
*/

class cache_lib
{
	var $identifier;
	var $crashed		= 0;
	
	var $link;
	
	
	function cache_lib( $identifier='' )
	{
		if( !function_exists('memcache_connect') )
		{
			$this->crashed = 1;
			return FALSE;
		}
		
		if( !$identifier )
		{
			$this->identifier = md5( uniqid( rand(), TRUE ) );
		}
		else
		{
			$this->identifier = $identifier;
		}
		
		unset( $identifier );
	}
	
	
	function connect( $server_info=array() )
	{
		if( !count($server_info) )
		{
			$this->crashed = 1;
			return FALSE;
		}
		
		if( !isset($server_info['memcache_server_1']) OR !isset($server_info['memcache_port_1']) )
		{
			$this->crashed = 1;
			return FALSE;
		}
		
		$this->link = memcache_connect( $server_info['memcache_server_1'], $server_info['memcache_port_1'] );
		
		if( !$this->link )
		{
			$this->crashed = 1;
			return FALSE;
		}
		
		if( isset($server_info['memcache_server_2']) AND isset($server_info['memcache_port_2']) )
		{
			memcache_add_server( $this->link, $server_info['memcache_server_2'], $server_info['memcache_port_2'] );
		}
		
		if( isset($server_info['memcache_server_3']) AND isset($server_info['memcache_port_3']) )
		{
			memcache_add_server( $this->link, $server_info['memcache_server_3'], $server_info['memcache_port_3'] );
		}
		
		if( function_exists('memcache_set_compress_threshold') )
		{
			memcache_set_compress_threshold( $this->link, 20000, 0.2 );
		}
		
		return TRUE;
	}
	
	
	function disconnect()
	{
		if( $this->link )
		{
			memcache_close( $this->link );
		}
		
		return TRUE;
	}
	
	
	function do_put( $key, $value, $ttl=0 )
	{
		memcache_set( $this->link, md5( $this->identifier . $key ),
							$value,
							MEMCACHE_COMPRESSED,
							intval($ttl) );
	}
	
	function do_get( $key )
	{
		$return_val = memcache_get( $this->link, md5( $this->identifier . $key ) );

		return $return_val;
	}
	
	function do_remove( $key )
	{
		memcache_delete( $this->link, md5( $this->identifier . $key ) );
	}
}
?>