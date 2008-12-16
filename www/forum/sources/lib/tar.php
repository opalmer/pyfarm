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
|   > $Date: 2007-09-17 18:05:43 -0400 (Mon, 17 Sep 2007) $
|   > $Revision: 1106 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Tar creation and extraction module
|   > Module written by Matt Mecham
|   > Usage style based on the C and Perl modules
|   > Will only work with PHP 4+
|   
|   > Date started: 15th Feb 2002
|
|	> Module Version Number: 1.0.0
|   > Module Author: Matthew Mecham
+--------------------------------------------------------------------------
|
| QUOTE OF THE MODULE:
|  If you can't find a program the does what you want it to do, write your
|  own.
|
+--------------------------------------------------------------------------
*/

/*************************************************************
|
| EXTRACTION USAGE:
|
| $tar = new tar();
| $tar->new_tar("/foo/bar", "myTar.tar");
| $files = $tar->list_files();
| $tar->extract_files( "/extract/to/here/dir" );
|
| CREATION USAGE:
|
| $tar = new tar();
| $tar->new_tar("/foo/bar" , "myNewTar.tar");
| $tar->current_dir("/foo" );  //Optional - tells the script which dir we are in
|                                to syncronise file creation from the tarball
| $tar->add_files( $file_names_with_path_array );
| (or $tar->add_directory( "/foo/bar/myDir" ); to archive a complete dir)
| $tar->write_tar();
|
*************************************************************/


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class tar {
	
	var $tar_header_length = '512';
	var $tar_unpack_header = 'a100filename/a8mode/a8uid/a8gid/a12size/a12mtime/a8chksum/a1typeflag/a100linkname/a6magic/a2version/a32uname/a32gname/a8devmajor/a8devminor/a155prefix';
	var $tar_pack_header   = 'A100 A8 A8 A8 A12 A12 A8 A1 A100 A6 A2 A32 A32 A8 A8 A155';
	var $current_dir       = "";
	var $unpack_dir        = "";
	var $pack_dir          = "";
	var $error             = "";
	var $work_dir          = array();
	var $tar_in_mem        = array();
	var $tar_filename      = "";
	var $filehandle        = "";
	var $warnings          = array();
	var $attributes        = array();
	var $tarfile_name      = "";
	var $tarfile_path      = "";
	var $tarfile_path_name = "";
	var $workfiles         = array();
	
	//-----------------------------------------
	// CONSTRUCTOR: Attempt to guess the current working dir.
	//-----------------------------------------
	
	function tar_init()
	{
		$this->ipsclass->load_language('lang_tar');
		
		if ( $this_dir = getcwd() )
		{
			$this->current_dir = $this_dir;
		}
		else if ( $this->ipsclass->my_getenv( 'DOCUMENT_ROOT' ) )
		{
			$this->current_dir = $this->ipsclass->my_getenv( 'DOCUMENT_ROOT' );
		}
		else
		{
			$this->current_dir = './';
		}
		
		// Set some attributes, these can be overriden later
		
		$this->attributes = array(  'over_write_existing'   => 0,
								    'over_write_newer'      => 0,
									'remove_tar_file'       => 0,
									'remove_original_files' => 0,
								 );
	}
	
	//-----------------------------------------
	// Set the tarname. If we are extracting a tarball, then it must be the
	// path to the tarball, and it's name (eg: $tar->new_tar("/foo/bar" ,'myTar.tar')
	// or if we are creating a tar, then it must be the path and name of the tar file
	// to create.
	//-----------------------------------------
	
	function new_tar($tarpath, $tarname) {
		
		$this->tarfile_name = $tarname;
		$this->tarfile_path = $tarpath;
		
		// Make sure there isn't a trailing slash on the path
		
		$this->tarfile_path = preg_replace( "#[/\\\]$#" , "" , $this->tarfile_path );
		
		$this->tarfile_path_name = $this->tarfile_path .'/'. $this->tarfile_name; 
		
	}
	
	
	//-----------------------------------------
	// Easy way to overwrite defaults
	//-----------------------------------------
	
	function over_write_existing() {
		$this->attributes['over_write_existing'] = 1;
	}
	function over_write_newer() {
		$this->attributes['over_write_newer'] = 1;
	}
	function remove_tar_file() {
		$this->attributes['remove_tar_file'] = 1;
	}
	function remove_original_files() {
		$this->attributes['remove_original_files'] = 1;
	}
	
	
	
	//-----------------------------------------
	// User assigns the root directory for the tar ball creation/extraction
	//-----------------------------------------
	
	function current_dir($dir = "") {
		
		$this->current_dir = $dir;
		
	}
	
	//-----------------------------------------
	// list files: returns an array with all the filenames in the tar file
	//-----------------------------------------
	
	function list_files($advanced="") {
	
		// $advanced == "" - return name only
		// $advanced == 1  - return name, size, mtime, mode
	
		$data = $this->read_tar();
		
		$final = array();
		
		foreach($data as $d)
		{
			if ($advanced == 1)
			{
				$final[] = array ( 'name'  => $d['name'],
								   'size'  => $d['size'],
								   'mtime' => $d['mtime'],
								   'mode'  => substr(decoct( $d['mode'] ), -4),
								 );
			}
			else
			{
				$final[] = $d['name'];
			}
		}
		
		return $final;
	}
	
	//-----------------------------------------
	// Add a directory to the tar files.
	// $tar->add_directory( str(TO DIRECTORY) )
	//    Can be used in the following methods.
	//	  $tar->add_directory( "/foo/bar" );
	//	  $tar->write_tar( "/foo/bar" );
	//-----------------------------------------
	
	function add_directory( $dir ) {
	
		$this->error = "";
	
		// Make sure the $to_dir is pointing to a valid dir, or we error
		// and return
		
		if (! is_dir($dir) )
		{
			$this->error = sprintf( $this->ipsclass->lang['error_tar1'], $to_dir );
			return FALSE;
		}
		
		$cur_dir = getcwd();
		chdir($dir);
		
		$this->get_dir_contents("./");
		
		$this->add_files($this->workfiles, $dir);
		
		chdir($cur_dir);
		
	}
	
	//-----------------------------------------
	
	function get_dir_contents( $dir )
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
						if (is_dir($dir."/".$filename))
						{
							$this->get_dir_contents($dir."/".$filename);
						}
						else
						{
							$this->workfiles[] = $dir."/".$filename;
						}
					}
				}
				
				closedir($handle);
			}
			else
			{
				$this->error = sprintf( $this->ipsclass->lang['error_tar2'], $dir );
				return FALSE;
			}
		}
		else
		{
			$this->error = sprintf( $this->ipsclass->lang['error_tar3'], $dir );
			return;
		}
	}
	
	//-----------------------------------------
	// Extract the tarball
	// $tar->extract_files( str(TO DIRECTORY), [ array( FILENAMES )  ] )
	//    Can be used in the following methods.
	//	  $tar->extract( "/foo/bar" , $files );
	// 	  This will seek out the files in the user array and extract them
	//    $tar->extract( "/foo/bar" );
	//    Will extract the complete tar file into the user specified directory
	//-----------------------------------------
	
	function extract_files( $to_dir, $files="" ) {
	
		$this->error = "";
	
		// Make sure the $to_dir is pointing to a valid dir, or we error
		// and return
		
		if (! is_dir($to_dir) )
		{
			$this->error = sprintf( $this->ipsclass->lang['error_tar1'], $to_dir );
			return;
		}
		
		//-----------------------------------------
		// change into the directory chosen by the user.
		//-----------------------------------------
		
		chdir($to_dir);
		$cur_dir = getcwd();
		
		$to_dir_slash = $to_dir . "/";
		
		//-----------------------------------------
		// Get the file info from the tar
		//-----------------------------------------
		
		$in_files = $this->read_tar();
		
		if ($this->error != "") {
			return;
		}
		
		foreach ($in_files as $file)
		{
			//-----------------------------------------
			// Stop any potential file traversal issues
			//-----------------------------------------
			
			$file['name'] = str_replace( '..', '', $file['name'] );
			
			//-----------------------------------------
			// Are we choosing which files to extract?
			//-----------------------------------------
			
			if ( is_array($files) )
			{
				if (! in_array($file['name'], $files) )
				{
					continue;
				}
			}
			
			chdir($cur_dir);
			
			//-----------------------------------------
			// GNU TAR format dictates that all paths *must* be in the *nix
			// format - if this is not the case, blame the tar vendor, not me!
			//-----------------------------------------
			
			if ( preg_match("#/#", $file['name']) )
			{
				$path_info = explode( "/" , $file['name'] );
				$file_name = array_pop($path_info);
			}
			else
			{
				$path_info = array();
				$file_name = $file['name'];
			}
			
			//-----------------------------------------
			// If we have a path, then we must build the directory tree
			//-----------------------------------------
			
			
			if (count($path_info) > 0)
			{
				foreach($path_info as $dir_component)
				{
					if ($dir_component == "")
					{
						continue;
					}
					if ( (file_exists($dir_component)) && (! is_dir($dir_component)) )
					{
						$this->warnings[] = sprintf( $this->ipsclass->lang['error_tar4'], $dir_component );
						continue;
					}
					if (! is_dir($dir_component))
					{
						mkdir( $dir_component, 0777);
						chmod( $dir_component, 0777);
					}
					
					if (! @chdir($dir_component))
					{
						$this->warnings[] = sprintf( $this->ipsclass->lang['error_tar5'], $dir_component );
					}
				}
			}
			
			//-----------------------------------------
			// check the typeflags, and work accordingly
			//-----------------------------------------
			
			if (($file['typeflag'] == 0) or (!$file['typeflag']) or ($file['typeflag'] == ""))
			{
				if ( $FH = fopen($file_name, "wb") )
				{
					fputs( $FH, $file['data'], strlen($file['data']) );
					fclose($FH);
				}
				else
				{
					$this->warnings[] = sprintf( $this->ipsclass->lang['error_tar6'], $file_name );
				}
			}
			else if ($file['typeflag'] == 5)
			{
				if ( (file_exists($file_name)) && (! is_dir($file_name)) )
				{
					$this->warnings[] = sprintf( $this->ipsclass->lang['error_tar7'], $file_name );
					continue;
				}
				if (! is_dir($file_name))
				{
					@mkdir( $file_name, 0777);
				}
			}
			else if ($file['typeflag'] == 6)
			{
				$this->warnings[] = $this->ipsclass->lang['error_tar8'];
				continue;
			}
			else if ($file['typeflag'] == 1)
			{
				$this->warnings[] = $this->ipsclass->lang['error_tar9'];
			}
			else if ($file['typeflag'] == 4)
			{
				$this->warnings[] = $this->ipsclass->lang['error_tar11'];
			}	
			else if ($file['typeflag'] == 3)
			{
				$this->warnings[] = $this->ipsclass->lang['error_tar11'];
			}
			else
			{
				$this->warnings[] = $this->ipsclass->lang['error_tar12'];
			}
			
			if (! @chmod( $file_name, $file['mode'] ) )
			{
				$this->warnings[] = sprintf( $this->ipsclass->lang['error_tar13'], $mode, $file_name );
			}
			
			@touch( $file_name, $file['mtime'] );
			
		}
		
		// Return to the "real" directory the scripts are in
		
		@chdir($this->current_dir);
		
	}
		
	//-----------------------------------------
	// add files:
	//  Takes an array of files, and adds them to the tar file
	//  Optionally takes a path to use as root for the tar file - if omitted, it
	//  assumes the current working directory is the tarball root. Be careful with
	//  this, or you may get unexpected results -especially when attempting to read
	//  and add files to the tarball.
	//  EXAMPLE DIR STRUCTURE
	//  /usr/home/somedir/forums/sources
	//  BRIEF: To tar up the sources directory
	// $files = array( 'sources/somescript.php', 'sources/anothersscript.php' );
	//  If CWD is 'somedir', you'll need to use $tar->add_files( $files, "/usr/home/somedir/forums" );
	//  or it'll attempt to open /usr/home/somedir/sources/somescript.php - which would result
	//  in an error. Either that, or use:
	//  chdir("/usr/home/somedir/forums");
	//  $tar->add_files( $files );
	//-----------------------------------------
	
	function add_files( $files, $root_path="" )
	{
		// Do we a root path to change into?
		
		if ($root_path != "")
		{
			chdir($root_path);
		}
		
		$count    = 0;
		
		foreach ($files as $file)
		{
			// is it a Mac OS X work file?
			
			if ( preg_match("/\.ds_store/i", $file ) )
			{
				continue;
			}
		
			$typeflag = 0;
			$data     = "";
			$linkname = "";
			
			$stat = stat($file);
			
			// Did stat fail?
			
			if (! is_array($stat) )
			{
				$this->warnings[] = sprintf( $this->ipsclass->lang['error_tar14'], $file );
				continue;
			}
			
			$mode  = fileperms($file);
			$uid   = $stat[4];
			$gid   = $stat[5];
			$rdev  = $stat[6];
			$size  = filesize($file);
			$mtime = filemtime($file);
			
			if (is_file($file))
			{
				// It's a plain file, so lets suck it up
				
				$typeflag = 0;
				
				if ( $FH = fopen($file, 'rb') )
				{
					$data = fread( $FH, filesize($file) );
					fclose($FH);
				}
				else
				{
					$this->warnings[] = sprintf( $this->ipsclass->lang['error_tar15'], $file );
					continue;
				}
			}
			else if (is_link($file))
			{
				$typeflag = 1;
				$linkname = @readlink($file);
			}
			else if (is_dir($file))
			{
				$typeflag = 5;
			}
			else
			{
				// Sockets, Pipes and char/block specials are not
				// supported, so - lets use a silly value to keep the
				// tar ball legitimate.
				$typeflag = 9;
			}
			
			// Add this data to our in memory tar file
			
			$this->tar_in_mem[] = array (
										  'name'     => $file,
										  'mode'     => $mode,
										  'uid'      => $uid,
										  'gid'      => $gid,
										  'size'     => strlen($data),
										  'mtime'    => $mtime,
										  'chksum'   => "      ",
										  'typeflag' => $typeflag,
										  'linkname' => $linkname,
										  'magic'    => "ustar\0",
										  'version'  => '00',
										  'uname'    => 'unknown',
										  'gname'    => 'unknown',
										  'devmajor' => "",
										  'devminor' => "",
										  'prefix'   => "",
										  'data'     => $data
										);
			// Clear the stat cache
			
			@clearstatcache();
			
			$count++;
		}
		
		@chdir($this->current_dir);
		
		//Return the number of files to anyone who's interested
		
		return $count;
	
	}
	
	//-----------------------------------------
	// write_tar:
	// Writes the tarball into the directory specified in new_tar with a filename
	// specified in new_tar
	//-----------------------------------------
	
	function write_tar() {
	
		if ($this->tarfile_path_name == "") {
			$this->error = $this->ipsclass->lang['error_tar16'];
			return;
		}
		
		if ( count($this->tar_in_mem) < 1 ) {
			$this->error = $this->ipsclass->lang['error_tar17'];
			return;
		}
		
		$tardata = "";
		
		foreach ($this->tar_in_mem as $file) {
		
			$prefix = "";
			$tmp    = "";
			$last   = "";
		
			// make sure the filename isn't longer than 99 characters.
			
			if (strlen($file['name']) > 99)
			{
				$pos = strrpos( $file['name'], "/" );
				
				if (is_string($pos) && !$pos)
				{
					// filename alone is longer than 99 characters!
					$this->error[] = sprintf( $this->ipsclass->lang['error_tar18'], $file['name'] );
					continue;
				}
				
				$prefix = substr( $file['name'], 0 , $pos );  // Move the path to the prefix
				$file['name'] = substr( $file['name'], ($pos+1));
				
				if (strlen($prefix) > 154)
				{
					$this->error[] = $this->ipsclass->lang['error_tar19'];
					continue;
				}
			}
			
			// BEGIN FORMATTING (a8a1a100)
			
			$mode  = sprintf("%6s ", decoct($file['mode']));
			$uid   = sprintf("%6s ", decoct($file['uid']));
			$gid   = sprintf("%6s ", decoct($file['gid']));
			$size  = sprintf("%11s ", decoct($file['size']));
			$mtime = sprintf("%11s ", decoct($file['mtime']));
			
			$tmp  = pack("a100a8a8a8a12a12",$file['name'],$mode,$uid,$gid,$size,$mtime);
						
			$last  = pack("a1"   , $file['typeflag']);
			$last .= pack("a100" , $file['linkname']);
								
			$last .= pack("a6", "ustar"); // magic
			$last .= pack("a2", "" ); // version
			$last .= pack("a32", $file['uname']);
			$last .= pack("a32", $file['gname']);
			$last .= pack("a8", ""); // devmajor
			$last .= pack("a8", ""); // devminor
			$last .= pack("a155", $prefix);
			//$last .= pack("a12", "");
			$test_len = $tmp . $last . "12345678";
			$last .= $this->internal_build_string( "\0" , ($this->tar_header_length - strlen($test_len)) );
			
			// Here comes the science bit, handling
			// the checksum.
			
			$checksum = 0;
			
			for ($i = 0 ; $i < 148 ; $i++ )
			{
				$checksum += ord( substr($tmp, $i, 1) );
			}
			
			for ($i = 148 ; $i < 156 ; $i++)
			{
				$checksum += ord(' ');
			}
			
			for ($i = 156, $j = 0 ; $i < 512 ; $i++, $j++)
			{
				$checksum += ord( substr($last, $j, 1) );
			}
			
			$checksum = sprintf( "%6s ", decoct($checksum) );
			
			$tmp .= pack("a8", $checksum);
			
			$tmp .= $last;
		   	
		   	$tmp .= $file['data'];
		   	
		   	// Tidy up this chunk to the power of 512
		   	
		   	if ($file['size'] > 0)
		   	{
		   		if ($file['size'] % 512 != 0)
		   		{
		   			$homer = $this->internal_build_string( "\0" , (512 - ($file['size'] % 512)) );
		   			$tmp .= $homer;
		   		}
		   	}
		   	
		   	$tardata .= $tmp;
		}
		
		// Add the footer
		
		$tardata .= pack( "a512", "" );
		
		// print it to the tar file
		
		$FH = fopen( $this->tarfile_path_name, 'wb' );
		fputs( $FH, $tardata, strlen($tardata) );
		fclose($FH);
		
		@chmod( $this->tarfile_path_name, 0777);
		
		// Done..
	}
		   
	//-----------------------------------------
	// Read the tarball - builds an associative array
	//-----------------------------------------
	
	function read_tar() {
	
		$filename = $this->tarfile_path_name;
	
		if ($filename == "") {
			$this->error = $this->ipsclass->lang['error_tar21'];
			return array();
		}
		
		if (! file_exists($filename) ) {
			$this->error = $this->ipsclass->lang['error_tar22'] . $filename;
			return array();
		}
		
		$tar_info = array();
		
		$this->tar_filename = $filename;
		
		// Open up the tar file and start the loop

		if (! $FH = fopen( $filename , 'rb' ) ) {
			$this->error = sprintf( $this->ipsclass->lang['error_tar23'], $filename );
			return array();
		}
		
		// Grrr, perl allows spaces, PHP doesn't. Pack strings are hard to read without
		// them, so to save my sanity, I'll create them with spaces and remove them here
		
		$this->tar_unpack_header = preg_replace( "/\s/", "" , $this->tar_unpack_header);
		
		while (!feof($FH)) {
		
			$buffer = fread( $FH , $this->tar_header_length );
			
			// check the block
			
			$checksum = 0;
			
			for ($i = 0 ; $i < 148 ; $i++) {
				$checksum += ord( substr($buffer, $i, 1) );
			}
			for ($i = 148 ; $i < 156 ; $i++) {
				$checksum += ord(' ');
			}
			for ($i = 156 ; $i < 512 ; $i++) {
				$checksum += ord( substr($buffer, $i, 1) );
			}
			
			$fa = unpack( $this->tar_unpack_header, $buffer);

			$name     = trim($fa[filename]);
			$mode     = OctDec(trim($fa[mode]));
			$uid      = OctDec(trim($fa[uid]));
			$gid      = OctDec(trim($fa[gid]));
			$size     = OctDec(trim($fa[size]));
			$mtime    = OctDec(trim($fa[mtime]));
			$chksum   = OctDec(trim($fa[chksum]));
			$typeflag = trim($fa[typeflag]);
			$linkname = trim($fa[linkname]);
			$magic    = trim($fa[magic]);
			$version  = trim($fa[version]);
			$uname    = trim($fa[uname]);
			$gname    = trim($fa[gname]);
			$devmajor = OctDec(trim($fa[devmajor]));
			$devminor = OctDec(trim($fa[devminor]));
			$prefix   = trim($fa[prefix]);
			
			if ( ($checksum == 256) && ($chksum == 0) ) {
				//EOF!
				break;
			}
			
			if ($prefix) {
				$name = $prefix.'/'.$name;
			}
			
			// Some broken tars don't set the type flag
			// correctly for directories, so we assume that
			// if it ends in / it's a directory...
			
			if ( (preg_match( "#/$#" , $name)) and (! $name) ) {
				$typeflag = 5;
			}
			
			// If it's the end of the tarball...
			$test = $this->internal_build_string( '\0' , 512 );
			if ($buffer == $test) {
				break;
			}
			
			// Read the next chunk
			
			$data = fread( $FH, $size );
			
			if (strlen($data) != $size) {
				$this->error = $this->ipsclass->lang['error_tar24'];
				fclose( $FH );
				return array();
			}
			
			$diff = $size % 512;
			
			if ($diff != 0) {
				// Padding, throw away
				$crap = fread( $FH, (512-$diff) );
			}
			
			// Protect against tarfiles with garbage at the end
			
			if ($name == "") {
				break;
			}
			
			$tar_info[] = array (
								  'name'     => $name,
								  'mode'     => $mode,
								  'uid'      => $uid,
								  'gid'      => $gid,
								  'size'     => $size,
								  'mtime'    => $mtime,
								  'chksum'   => $chksum,
								  'typeflag' => $typeflag,
								  'linkname' => $linkname,
								  'magic'    => $magic,
								  'version'  => $version,
								  'uname'    => $uname,
								  'gname'    => $gname,
								  'devmajor' => $devmajor,
								  'devminor' => $devminor,
								  'prefix'   => $prefix,
								  'data'     => $data
								 );
		}
		
		fclose($FH);
		
		return $tar_info;
	}
			





//-----------------------------------------
// INTERNAL FUNCTIONS - These should NOT be called outside this module
//+------------------------------------------------------------------------------
	
	//-----------------------------------------
	// build_string: Builds a repititive string
	//-----------------------------------------
	
	function internal_build_string($string="", $times=0) {
	
		$return = "";
		for ($i=0 ; $i < $times ; ++$i ) {
			$return .= $string;
		}
		
		return $return;
	}
	
	
	
	
	
}


?>