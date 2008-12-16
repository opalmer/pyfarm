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
|   > Posting STD Editor
|   > Module written by Matt Mecham
|   > Date started: Thursday 10th March 2005 11:38
|
+--------------------------------------------------------------------------
*/

/**
* Text Editor: Standard Class
*
* Class for parsing standard text editor
*
* @package		InvisionPowerBoard
* @subpackage	TextEditor
* @author  	 	Matt Mecham
* @version		2.1
* @since		2.1.0
* @ignore
*/

/**
*
*/

/**
* Text Editor: Standard Class
*
* Class for parsing standard text editor
*
* @package		InvisionPowerBoard
* @subpackage	TextEditor
* @author  	 	Matt Mecham
* @version		2.1
* @since		2.1.0
* @ignore
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class class_editor_module extends class_editor
{
	# Global
	var $ipsclass;
	
	
	/*-------------------------------------------------------------------------*/
	// Process the raw post with BBCode before showing in the form
	/*-------------------------------------------------------------------------*/
	
	function process_before_form( $t )
	{
		$t = str_replace( '<', '&lt;', $t );
		$t = str_replace( '>', '&gt;', $t );
		
		return $t;
	}
	
	/*-------------------------------------------------------------------------*/
	// Process the raw post with BBCode before saving
	/*-------------------------------------------------------------------------*/
	
	function process_after_form( $form_field )
	{
		return $this->_clean_post( $_POST[ $form_field ] );
	}
	
	
	
}


?>