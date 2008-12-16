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
|   > $Date: 2007-05-01 14:00:21 -0400 (Tue, 01 May 2007) $
|   > $Revision: 958 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > XML OUT Functions for XmlHttpRequest functions
|   > Module written by Matt Mecham
|   > Date started: Friday 18th March 2005
|
|	> Module Version Number: 1.1.0
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class ad_xmlout
{
	# Classes
	var $ipsclass;
	var $class_ajax;
	var $search;
	
    var $xml_output;
    var $xml_header;
    
    /*-------------------------------------------------------------------------*/
    // Constructor
    /*-------------------------------------------------------------------------*/
    
    function xmlout()
    {
    	$this->xml_header = '<?xml version="1.0" encoding="ISO-8859-1"?'.'>';
    }
    
    /*-------------------------------------------------------------------------*/
    // Auto run
    /*-------------------------------------------------------------------------*/
    
    function auto_run()
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	if ( isset($this->ipsclass->input['j_do']) AND $this->ipsclass->input['j_do'] )
    	{
    		$this->ipsclass->input['do'] = $this->ipsclass->input['j_do'];
    	}
    	
		//-----------------------------------------
		// Load ajax class
		//-----------------------------------------
		
		require_once( KERNEL_PATH . 'class_ajax.php' );

		$this->class_ajax           =  new class_ajax();
		$this->class_ajax->ipsclass =& $this->ipsclass;
    	$this->class_ajax->class_init();    	
    	
    	//-----------------------------------------
    	// What shall we do?
    	//-----------------------------------------
    	
    	switch( $this->ipsclass->input['do'] )
    	{
    		case 'get-template-names':
    			$this->get_template_names();
    			break;
    		case 'get-member-names':
    			$this->get_member_names();
    			break;
    		case 'get-dir-size':
    			$this->get_dir_size();
    			break;
			case 'post-editorswitch':
				$this->post_editorswitch();
				break;
			case 'captcha_test':
				$this->captcha_test();
				break;
    	}
    }

	/*-------------------------------------------------------------------------*/
	// Switch editors
	/*-------------------------------------------------------------------------*/
	
	function captcha_test()
	{
		//-----------------------------------------
		// Generate number
		//-----------------------------------------
		
		mt_srand( (double) microtime() * 1000000 );
		$final_rand = md5( uniqid( mt_rand(), TRUE ) );
		mt_srand(); 

		for( $i = 0; $i < 6; $i++ )
		{
			$captcha_string .= $final_rand{ mt_rand(0, 31) };
		}
		
		//-----------------------------------------
		// Generate image
		//-----------------------------------------
		
		require_once( KERNEL_PATH . 'class_captcha.php' );
		$class_captcha                  =  new class_captcha();
		$class_captcha->ipsclass        =& $this->ipsclass;
		$class_captcha->path_background =  ROOT_PATH . 'style_captcha/captcha_backgrounds';
		$class_captcha->path_fonts      =  ROOT_PATH . 'style_captcha/captcha_fonts';

		return $class_captcha->captcha_show_gd_img( $captcha_string );
	}
    
    /*-------------------------------------------------------------------------*/
	// Switch editors
	/*-------------------------------------------------------------------------*/
	
	function post_editorswitch()
	{
		//-----------------------------------------
		// Grab public class and do...
		//-----------------------------------------
		
		require_once( ROOT_PATH . 'sources/action_public/xmlout.php' );
		$lib           			=  new xmlout();
		$lib->ipsclass 			=& $this->ipsclass;
		
		require_once( KERNEL_PATH . 'class_ajax.php' );
		$class_ajax				= new class_ajax();
		$class_ajax->ipsclass 	=& $this->ipsclass;
		$lib->class_ajax		=& $class_ajax;
		
		return $lib->post_editorswitch();
	}
	
	/*-------------------------------------------------------------------------*/
	// Get uploads size
	/*-------------------------------------------------------------------------*/
	
	function get_dir_size()
	{
		//-----------------------------------------
		// Make sure the uploads path is correct
		//-----------------------------------------
		
		$uploads_size = 0;
		
		if ($dh = @opendir( $this->ipsclass->vars['upload_dir'] ))
		{
			while ( false !== ( $file = @readdir( $dh ) ) )
			{
				if ( ! preg_match( "/^..?$|^index/i", $file ) )
				{
					if( is_dir( $this->ipsclass->vars['upload_dir'] . "/" . $file ) )
					{
						if( $sub_dh = @opendir( $this->ipsclass->vars['upload_dir'] . "/" . $file ))
						{
							while ( false !== ( $sub_file = @readdir( $sub_dh ) ) )
							{
								if ( ! preg_match( "/^..?$|^index/i", $sub_file ) )
								{
									$uploads_size += @filesize( $this->ipsclass->vars['upload_dir'] . "/" . $file . "/" . $sub_file );
								}
							}
						}
					}
					else
					{
						$uploads_size += @filesize( $this->ipsclass->vars['upload_dir'] . "/" . $file );
					}
				}
			}
			@closedir( $dh );
		}
		
		//-----------------------------------------
		// This piece of code from Jesse's (jesse@jess.on.ca) contribution
		// to the PHP manual @ php.net posted without license
		//-----------------------------------------
		
		if ($uploads_size >= 1048576)
		{
			$uploads_size = round($uploads_size / 1048576 * 100 ) / 100 . " mb";
		}
		else if ($uploads_size >= 1024)
		{
			$uploads_size = round($uploads_size / 1024 * 100 ) / 100 . " k";
		}
		else
		{
			$uploads_size = $uploads_size . " bytes";
		}
		
		$this->class_ajax->print_nocache_headers();
		$this->class_ajax->return_string( $uploads_size );
	}    
    
     /*-------------------------------------------------------------------------*/
	// Get new posts
	/*-------------------------------------------------------------------------*/
      
    function get_member_names()
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	$name = $this->class_ajax->convert_and_make_safe( $this->ipsclass->input['name'], 0 );
    	
    	//--------------------------------------------
		// Load extra db cache file
		//--------------------------------------------
		
		$this->ipsclass->DB->load_cache_file( ROOT_PATH.'sources/sql/'.SQL_DRIVER.'_extra_queries.php', 'sql_extra_queries' );
		
    	//-----------------------------------------
    	// Check length
    	//-----------------------------------------
    	
    	if ( strlen( $name ) < 3 )
    	{
    		$this->class_ajax->return_null();
    	}
    	
    	//-----------------------------------------
    	// Try query...
    	//-----------------------------------------
    	
    	$this->ipsclass->DB->cache_add_query( 'member_display_name_lookup', array( 'name' => $name, 'field' => 'members_display_name' ), 'sql_extra_queries' );
		$this->ipsclass->DB->cache_exec_query();
		
    	//-----------------------------------------
    	// Got any results?
    	//-----------------------------------------
    	
    	if ( ! $this->ipsclass->DB->get_num_rows() )
 		{
    		$this->class_ajax->return_null();
    	}
    	
    	$names = array();
		$ids   = array();
		
		while( $r = $this->ipsclass->DB->fetch_row() )
		{
			$names[] = '"'.$r['members_display_name'].'"';
			$ids[]   = intval($r['id']);
		}
		
		$this->class_ajax->print_nocache_headers();
		@header( "Content-type: text/plain;charset={$this->ipsclass->vars['gb_char_set']}" );
		print "returnSearch( '".str_replace( "'", "", $name )."', new Array(".implode( ",", $names )."), new Array(".implode( ",", $ids ).") );";
		exit();
    }
    
    /*-------------------------------------------------------------------------*/
	// Get template names
	/*-------------------------------------------------------------------------*/
      
    function get_template_names()
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	$name = $this->class_ajax->convert_and_make_safe( $this->ipsclass->input['name'], 0 );
    	$id   = intval( $this->ipsclass->input['id'] );
    	
    	//-----------------------------------------
		// Get $skin_names stuff
		//-----------------------------------------
		
		require_once( ROOT_PATH.'sources/lib/skin_info.php' );
		
    	//-----------------------------------------
    	// Check length
    	//-----------------------------------------
    	
    	if ( strlen( $name ) < 3 )
    	{
    		$this->class_ajax->return_null();
    	}
    	
    	//-----------------------------------------
    	// Try query...
    	//-----------------------------------------
    	
    	$this->ipsclass->DB->build_query( array( 'select' => "suid,set_id,group_name,func_name",
    											 'from'   => 'skin_templates',
    											 'where'  => "set_id=1 AND LOWER(func_name) LIKE '{$name}%'",
    											 'order'  => 'LENGTH(func_name) ASC',
    											 'limit'  => array( 0,20 ) ) );
    											 
    	$this->ipsclass->DB->exec_query();
    	
    	$names = array();
		$ids   = array();
		
		while( $r = $this->ipsclass->DB->fetch_row() )
		{
			$names[] = '"'.$r['func_name'].' ('.$r['group_name'].')"';
			$ids[]   = intval($r['suid']);
		}
		
		$this->class_ajax->print_nocache_headers();
		@header( "Content-type: text/plain;charset={$this->ipsclass->vars['gb_char_set']}" );
		print "returnSearch( '".str_replace( "'", "", $name )."', new Array(".implode( ",", $names )."), new Array(".implode( ",", $ids ).") );";
		exit();
    }
    
   
  
}

?>