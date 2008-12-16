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
*	-- Turck-MMCache Cache Storage
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
	
	
	function cache_lib( $identifier='' )
	{
		if( !function_exists('mmcache_get') )
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
	
	
	function disconnect()
	{
		return TRUE;
	}
		
	
	function do_put( $key, $value, $ttl=0 )
	{
		mmcache_lock( md5( $this->identifier . $key ) );
		
		mmcache_put( md5( $this->identifier . $key ),
							$value,
							intval($ttl) );
							
		mmcache_unlock( md5( $this->identifier . $key ) );
	}
	
	function do_get( $key )
	{
		$return_val = mmcache_get( md5( $this->identifier . $key ) );

		return $return_val;
	}
	
	function do_remove( $key )
	{
		mmcache_rm( md5( $this->identifier . $key ) );
	}
}
?>