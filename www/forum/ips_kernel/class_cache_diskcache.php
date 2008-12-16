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
*	-- Hard-disk Cache Storage
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
		if( !is_writeable( ROOT_PATH.'cache' ) )
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
		// We ignore TTL
		
		$fh = fopen( ROOT_PATH.'cache/'.md5( $this->identifier . $key ).'.php', 'wb' );
		
		if( !$fh )
		{
			return FALSE;
		}
		
		$extra_flag = "";
		
		if( is_array( $value ) )
		{
			$value = serialize($value);
			$extra_flag = "\n".'$is_array = 1;'."\n\n";
		}
		
		$extra_flag .= "\n".'$ttl = '.$ttl.";\n\n";
		
		$value = '"'.addslashes( $value ).'"';
		
		$file_content = "<?"."php\n\n".'$value = '.$value.";\n".$extra_flag."\n?".'>';
		
		flock( $fh, LOCK_EX );
		fwrite( $fh, $file_content );
		flock( $fh, LOCK_UN );
		fclose( $fh );
		
		@chmod( ROOT_PATH.'cache/'.md5( $this->identifier . $key ).'.php', 0777 );
	}
	
	function do_get( $key )
	{
		$return_val = "";
		
		if( file_exists( ROOT_PATH.'cache/'.md5( $this->identifier . $key ).'.php' ) )
		{
			require ROOT_PATH.'cache/'.md5( $this->identifier . $key ).'.php';
			
			$return_val = stripslashes($value);

			if( isset($is_array) AND $is_array == 1 )
			{
				$return_val = unserialize($return_val);
			}
			
			if( isset($ttl) AND $ttl > 0 )
			{
				if( $mtime = filemtime( ROOT_PATH.'cache/'.md5( $this->identifier . $key ).'.php' ) )
				{
					if( time() - $mtime > $ttl )
					{
						return FALSE;
					}
				}
			}
		}

		return $return_val;
	}
	
	function do_remove( $key )
	{
		if( file_exists( ROOT_PATH.'cache/'.md5( $this->identifier . $key ).'.php' ) )
		{
			@unlink( ROOT_PATH.'cache/'.md5( $this->identifier . $key ).'.php' );
		}
	}
}
?>