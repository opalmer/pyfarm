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
|   > PORTAL PLUG IN MODULE: POLL
|   > Module written by Matt Mecham
|   > Date started: Tuesday 2nd August 2005 (12:56)
+--------------------------------------------------------------------------
*/

/**
* Portal Plug In Module
*
* This module shows a poll. That's it.
*
* @package		InvisionPowerBoard
* @subpackage	PortalPlugIn
* @author		Matt Mecham
* @copyright	Invision Power Services, Inc.
* @version		2.1
*/

/**
* Portal Plug In Module
*
* This module shows a poll. Wooo
* Each class name MUST be in the format of:
* ppi_{file_name_minus_dot_php}
*
* @package		InvisionPowerBoard
* @subpackage	PortalPlugIn
* @author		Matt Mecham
* @copyright	Invision Power Services, Inc.
* @version		2.1
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class ppi_poll
{
	/**
	* IPS Global object
	*
	* @var string
	*/
	var $ipsclass;

	/**
	* Array of portal objects including:
	* good_forum, bad_forum
	*
	* @var array
	*/
	var $portal_object = array();
	
	/*-------------------------------------------------------------------------*/
 	// INIT
	/*-------------------------------------------------------------------------*/
 	/**
	* This function must be available always
	* Add any set up here, such as loading language and skins, etc
	*
	*/
 	function init()
 	{
 	}
 	
 	/*-------------------------------------------------------------------------*/
	// MAIN FUNCTION
	/*-------------------------------------------------------------------------*/
	/**
	* Main function
	*
	* @return VOID
	*/
	function poll_show_poll()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
 		$extra = "";
 		$sql   = "";
 		$check = 0;
 		
 		//-----------------------------------------
 		// Got a poll?
 		//-----------------------------------------
 		
 		if ( ! $this->ipsclass->vars['poll_poll_url'] )
 		{
 			return;
 		}
 		
 		//-----------------------------------------
		// Get the topic ID of the entered URL
		//-----------------------------------------
		
		preg_match( "/(\?|&amp;)?(t|showtopic)=(\d+)($|&amp;)/", $this->ipsclass->vars['poll_poll_url'], $match );
		
		$tid = intval(trim($match[3]));
		
		if ($tid == "")
		{
			return;
		}
		
		//-----------------------------------------
		// Get topic...
		//-----------------------------------------
		
		require_once( ROOT_PATH.'sources/action_public/topics.php' );
		$this->topic = new topics();
		$this->topic->ipsclass =& $this->ipsclass;
		$this->topic->topic_init();
		
		$this->topic->topic = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																				'from'   => 'topics',
																				'where'  => "tid=".$tid,
																	  )      );
							
		$this->topic->forum = $this->ipsclass->forums->forum_by_id[ $this->topic->topic['forum_id'] ];
		
		$this->ipsclass->input['f'] = $this->topic->forum['id'];
		$this->ipsclass->input['t'] = $tid;
		
		if ( $this->topic->topic['poll_state'] )
		{
			$html = $this->topic->parse_poll();
		
 			return $this->ipsclass->compiled_templates['skin_portal']->tmpl_poll_wrapper( $html, $tid );
 		}
 		else
 		{
 			return;
 		}
 	}

}

?>