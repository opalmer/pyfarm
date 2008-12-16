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
|   > $Date: 2006-09-22 06:28:31 -0400 (Fri, 22 Sep 2006) $
|   > $Revision: 567 $
|   > $Author: matt $
+---------------------------------------------------------------------------
|
|   > Post Handler
|   > Module written by Matt Mecham
|   > Date started: Wednesday 9th March 2005 (15:23)
|
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class han_post
{
	# Global
	var $ipsclass;
	var $class_post;
	
	# Method
	var $method;
	
	# Forum
	var $forum;
	var $md5_check;
	
	var $modules = array();
	
	var $obj = array();
	
    /*-------------------------------------------------------------------------*/
    // INIT
    /*-------------------------------------------------------------------------*/
    
    function init()
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	$class = "";
    	
    	//-----------------------------------------
    	// Which class
    	//-----------------------------------------
    	
    	switch( $this->method )
    	{
    		case 'new':
    			$class = 'class_post_new.php';
    			break;
    		case 'reply':
    			$class = 'class_post_reply.php';
    			break;
    		case 'poll':
    			$class = 'class_post_poll.php';
    			break;
    		case 'edit':
    			$class = 'class_post_edit.php';
    			break;
    		
    		default:
    			$class = 'class_post_new.php';
    	}
    	
		//-----------------------------------------
		// Load classes
		//-----------------------------------------
		
		require_once( ROOT_PATH . 'sources/classes/post/class_post.php' );
		require_once( ROOT_PATH . 'sources/classes/post/'.$class );
		
		$this->class_post             =  new post_functions();
		$this->class_post->ipsclass   =& $this->ipsclass;
		$this->class_post->forum      =& $this->forum;
		$this->class_post->md5_check  = $this->md5_check;
		$this->class_post->obj        = $this->obj;
		$this->class_post->modules    = $this->modules;
		
		//-----------------------------------------
		// Init class
		//-----------------------------------------
		
        $this->class_post->main_init();
    }
    
    /*-------------------------------------------------------------------------*/
    // Mode: Save post in DB
    /*-------------------------------------------------------------------------*/
  
  	function show_form()
  	{
  		return $this->class_post->show_form();
  	}
  	
    /*-------------------------------------------------------------------------*/
    // Mode: Save post in DB
    /*-------------------------------------------------------------------------*/
  
  	function save_post()
  	{
  		return $this->class_post->save_post();
  	}

    /*-------------------------------------------------------------------------*/
    // Mode: Process
    /*-------------------------------------------------------------------------*/
  
  	function process_post()
  	{
  		return $this->class_post->process_post();
  	}

	
	
}

?>