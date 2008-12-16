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
|   > $Date: 2006-06-23 10:44:15 +0100 (Fri, 23 Jun 2006) $
|   > $Revision: 338 $
|   > $Author: matt $
+---------------------------------------------------------------------------
|
|   > Virus Checker CLASS
|   > Module written by Brandon Farber / Matt Mecham
|   > Date started: Monday 3rd July 2006
|
|	> Module Version Number: 1.0.0
|   > DBA Checked: Wed 19 May 2004
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class class_virus_checker
{

	/**
	* Global
	*/
	var $ipsclass;
	
	/**
	* Directory separator
	*/
	var $dir_split = "/";
	
	/**
	* Dodgy files
	* @var	array [ idx ] = ( file_path, file_name );
	*/
	var $bad_files = array();
	
	/**
	* Checked folders
	*/
	var $checked_folders = array();
	
	/**
	* Known names
	*/
	var $known_names = array();
	
	/*-------------------------------------------------------------------------*/
	// CONSTRUCTAA
	/*-------------------------------------------------------------------------*/
	
	function class_virus_checker()
	{
		set_time_limit(0);
		
		if ( strtoupper( substr(PHP_OS, 0, 3) ) === 'WIN' )
		{
			$this->dir_split = "\\";
		}
		
		//-----------------------------------------
		// Known names
		//-----------------------------------------
		
		require( ROOT_PATH . 'sources/classes/class_virus_checker/lib_known_names.php' );
		
		$this->known_names = $KNOWN_NAMES;
	}
	
	/*-------------------------------------------------------------------------*/
	// Run the scan
	/*-------------------------------------------------------------------------*/
	/**
	* Runs the scan
	* All suspicious files are entered into
	* $this->bad_files array
	*/
	
	function run_scan()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$expected	= array();
		$root_dir 	= preg_replace( "#^(.+?)\/$#", "\\1", ROOT_PATH );
		$skin_dirs  = array();
		
		//-----------------------------------------
		// Load extra db cache file
		//-----------------------------------------
		
		$this->ipsclass->DB->load_cache_file( ROOT_PATH.'sources/sql/'.SQL_DRIVER.'_extra_queries.php', 'sql_extra_queries' );
		
		//-----------------------------------------
		// Get libs
		//-----------------------------------------
		
		require( ROOT_PATH . 'sources/classes/class_virus_checker/lib_writeable_dirs.php' );
		require( ROOT_PATH . 'sources/classes/class_virus_checker/lib_lang_files.php' );
		
		//-----------------------------------------
		// Sort out directory separator
		//-----------------------------------------
		
		if ( $this->dir_split != '/' )
		{
			$_WRITEABLE_DIRS = $WRITEABLE_DIRS;
			$WRITEABLE_DIRS  = array();
			
			foreach( $_WRITEABLE_DIRS as $dir )
			{
				$WRITEABLE_DIRS[] = str_replace( '/' , $this->dir_split, $dir );
			}
		}
		
		//-----------------------------------------
		// Get language directories
		//-----------------------------------------
				
		if ( isset($this->ipsclass->cache['languages']) AND is_array( $this->ipsclass->cache['languages'] ) && count( $this->ipsclass->cache['languages'] ) )
		{
			foreach( $this->ipsclass->cache['languages'] as $v )
			{
				$WRITEABLE_DIRS[] = 'cache'.$this->dir_split.'lang_cache'.$this->dir_split.$v['ldir'];
				
				foreach( $LANG_FILES as $filename )
				{
					$expected[] = 'cache'.$this->dir_split.'lang_cache'.$this->dir_split.$v['ldir'].$this->dir_split.$filename.'.php';
				}
			}
		}
		else
		{
			$this->ipsclass->DB->build_query( array( 'select' => 'ldir', 'from' => 'languages' ) );
			$this->ipsclass->DB->exec_query();
			
			while( $v = $this->ipsclass->DB->fetch_row() )
			{
				$WRITEABLE_DIRS[] = 'cache'.$this->dir_split.'lang_cache'.$this->dir_split.$v['ldir'];
				
				foreach( $LANG_FILES as $filename )
				{
					$expected[] = 'cache'.$this->dir_split.'lang_cache'.$this->dir_split.$v['ldir'].$this->dir_split.$filename.'.php';
				}				
			}
		}
		
		//-----------------------------------------		
		// Get skin directories
		//-----------------------------------------
				
		if ( is_array( $this->ipsclass->cache['skin_id_cache'] ) && count( $this->ipsclass->cache['skin_id_cache'] ) )
		{
			foreach( $this->ipsclass->cache['skin_id_cache'] as $k => $v )
			{
				if ( $k == 1 && !IN_DEV )
				{
					continue;
				}
				
				$WRITEABLE_DIRS[] = 'cache'.$this->dir_split.'skin_cache'.$this->dir_split.'cacheid_'.$v['set_skin_set_id'];
				$skin_dirs[] = $v['set_skin_set_id'];
			}
		}
		else
		{
			$this->ipsclass->DB->build_query( array( 'select' => 'set_skin_set_id', 'from' => 'skin_sets' ) );
			$this->ipsclass->DB->exec_query();
			
			while( $v = $this->ipsclass->DB->fetch_row() )
			{
				$WRITEABLE_DIRS[] = 'cache'.$this->dir_split.'skin_cache'.$this->dir_split.'cacheid_'.$v['set_skin_set_id '];
				$skin_dirs[] = $v['set_skin_set_id'];
			}
		}
		
		//-----------------------------------------		
		// Get skin files
		//-----------------------------------------
		
		$this->ipsclass->DB->cache_add_query( 'diag_distinct_skins', array(), 'sql_extra_queries' );
		$this->ipsclass->DB->cache_exec_query();
	
		while( $v = $this->ipsclass->DB->fetch_row() )
		{
			foreach( $skin_dirs as $dir )
			{
				$expected[] = 'cache'.$this->dir_split.'skin_cache'.$this->dir_split.'cacheid_'.$dir.$this->dir_split.$v['group_name'].'.php';
			}
		}
		
		//-----------------------------------------
		// Alright, do it!
		//-----------------------------------------
		
		$WRITEABLE_DIRS = array_unique($WRITEABLE_DIRS);
		
		foreach( $WRITEABLE_DIRS as $dir_to_check )
		{
			if ( $dir_to_check == 'uploads' OR $dir_to_check == 'style_emoticons' )
			{
				# Leave this 'til later
				continue;
			}
			
			if ( file_exists( $root_dir . $this->dir_split . $dir_to_check ) )
			{
				$this->checked_folders[] = $root_dir . $this->dir_split . $dir_to_check;
				
				$dh = opendir( $root_dir . $this->dir_split . $dir_to_check );
				
				while ( false !== ( $file = readdir($dh) ) )
				{ 
					if ( preg_match( "#.*\.(php|js|html|htm|cgi|pl|perl|php3|php4|php5|php6)$#i", $file ) )
					{
						if ( ! in_array( $dir_to_check.$this->dir_split.$file, $expected ) AND $file != "index.html" AND $file != 'lang_javascript.js' )
						{
							$score = intval( $this->score_file( $root_dir.$this->dir_split.$dir_to_check.$this->dir_split.$file ) );
							
							$this->bad_files[] = array( 'file_path' => $root_dir.$this->dir_split.$dir_to_check.$this->dir_split.$file,
														'file_name' => $file,
														'score'     => $score );
						}
					}
				}
			
				@closedir($dh);
			}
		}
		
		//-----------------------------------------
		// Check 'blog' dir
		//-----------------------------------------
		
		if ( file_exists( $root_dir.$this->dir_split.'blog' ) )
		{
			$this->anti_virus_deep_scan( $root_dir.$this->dir_split.'blog', 'all', array( 'index.php' ) );
		}
		
		//-----------------------------------------
		// Check 'html' dir
		//-----------------------------------------
		
		if ( file_exists( $root_dir.$this->dir_split.'html' ) )
		{
			$this->anti_virus_deep_scan( $root_dir.$this->dir_split.'html', 'all', array( 'index.php' ) );
		}
		
		//-----------------------------------------
		// Check 'Skin' dir
		//-----------------------------------------
		
		if ( file_exists( $root_dir.$this->dir_split.'Skin' ) )
		{
			$this->anti_virus_deep_scan( $root_dir.$this->dir_split.'Skin', 'all' );
		}
		
		//-----------------------------------------
		// Check emoticons
		//-----------------------------------------
		
		$this->anti_virus_deep_scan( $root_dir.$this->dir_split.'style_emoticons', 'all' );
		
		//-----------------------------------------
		// Check image directories
		//-----------------------------------------
		
		$this->anti_virus_deep_scan( $root_dir.$this->dir_split.'style_images', '(php|cgi|pl|perl|php3|php4|php5|php6|phtml|shtml)' );
		
		//-----------------------------------------
		// Check upload directories
		//-----------------------------------------
		
		$this->anti_virus_deep_scan( $root_dir.$this->dir_split.'uploads' );
	}
	
	/*-------------------------------------------------------------------------*/
	// Score a file
	/*-------------------------------------------------------------------------*/
	/**
	* Score a file
	*
	* Return a score from 0 being harmless to 10 being, well the complete opposite to harmless.
	*
	* Score information:
	* Name 3 chars or less + 2
	* '- Name 2 chars or less + 4
	* Size over 65k + 3
	* '- Size over 100k + 4
	* User nobody + 3
	* Modified in the 30 days + 1
	* In non PHP folder + 3
	*
	* @param	string	Full file path and name
	* @param	array 	stat info
	*
	* @return	int 	Score (0 - 10 )
	*/
	function score_file( $file_name, $stat=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$SCORE         = 0; 
		$name          = preg_replace( "#^(.*)/(.+?)$#is" , "\\2", $file_name );
		$name_sans_ext = preg_replace( "#^(.*)\.(.+?)$#si", "\\1", $name );
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
	
		if ( ! $file_name )
		{
			return -1;
		}
		
		if ( ! is_array( $stat ) OR ! count( $stat ) )
		{
			$stat = stat( $file_name );
		}
		
		//-----------------------------------------
		// Alright...
		//-----------------------------------------
		
		if ( in_array( $file_name, $this->known_names ) )
		{
			$SCORE += 7;
		}
		
		//-----------------------------------------
		// User nobody?
		//-----------------------------------------
		
		if ( $stat['uid'] == 99 )
		{
			$SCORE += 3;
		}
		
		if ( strlen( $name_sans_ext ) < 3 )
		{
			$SCORE += 4;
		}
		else if ( $name_sans_ext == 'temp' OR $name_sans_ext == 'test' )
		{
			$SCORE +=2;
		}
		else if ( strlen( $name_sans_ext ) < 4 )
		{
			$SCORE += 2;
		}
		
		//-----------------------------------------
		// Size
		//-----------------------------------------
		
		if ( $stat['size'] > 100 * 1024 )
		{
			$SCORE += 4;
		}
		else if ( $stat['size'] > 65 * 1024 )
		{
			$SCORE += 3;
		}
		
		//-----------------------------------------
		// Last modified...
		//-----------------------------------------
		
		if ( $stat['mtime'] > time() - 86400 * 30 )
		{
			$SCORE += 1;
		}
		
		//-----------------------------------------
		// Non PHP folder...
		//-----------------------------------------
		
		if ( preg_match( "#(?:style_images|style_emoticons|uploads|style_avatars|default|1|skin_acp|html|skin)/#i", $file_name ) )
		{
			$SCORE += 3;
		}
		
		//-----------------------------------------
		// Return
		//-----------------------------------------
	
		return $SCORE > 10 ? 10 : $SCORE;
	}
	
	/*-------------------------------------------------------------------------*/
	// Deep scan
	/*-------------------------------------------------------------------------*/
	/**
	* Deep scan
	*
	* All suspicious files are entered into
	* $this->bad_files array
	*/
	function anti_virus_deep_scan( $dir, $look_for='(php|js|html|htm|cgi|pl|perl|php3|php4|php5|php6|htaccess|so|phtml|shtml)', $ignore_files=array() )
	{
		//-----------------------------------------
		// Short-hand
		//-----------------------------------------
		
		if ( $look_for == 'all' )
		{
			$look_for = '(php|js|html|htm|cgi|pl|perl|php3|php4|php5|php6|htaccess|so|phtml|shtml)';
		}
		
		//-----------------------------------------
		// Add into checked folders
		//-----------------------------------------
		
		$this->checked_folders[] = $dir . $this->dir_split . $file;
		
		$dh = opendir( $dir );
		
		while ( false !== ( $file = readdir($dh) ) )
		{
			if ( IN_DEV )
			{
				if ( $file == '.' or $file == '..' or $file == '.svn' or $file == '.DS_store' or $file == 'index.html' )
				{
					continue;
				}
			}
			else
			{
				if ( $file == '.' or $file == '..' )
				{
					continue;
				}
			}
			
			if ( is_dir( $dir . $this->dir_split . $file ) )
			{
				$this->anti_virus_deep_scan( $dir . $this->dir_split . $file, $look_for );
			}
			else
			{
				if ( in_array( $dir . $this->dir_split . $file, $ignore_files ) )
				{
					continue;
				}
				
				if ( preg_match( "#^(.*)?\.".$look_for."(?:\..+?)?$#i", $file ) )
				{ 
					$score = intval( $this->score_file( $dir . $this->dir_split . $file ) );
					
					$this->bad_files[] = array( 'file_path' => $dir . $this->dir_split . $file,
												'file_name' => $file,
												'score'     => $score );
				}
			}
		}
	}
}


?>