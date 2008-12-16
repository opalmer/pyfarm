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
|   > $Date: 2005-10-10 14:08:54 +0100 (Mon, 10 Oct 2005) $
|   > $Revision: 23 $
|   > $Author: matt $
+---------------------------------------------------------------------------
|
|   > API: Topic Subscriptions
|   > Module written by Brandon Farber
|   > Date started: Tuesday 3rd April 2007 (10:25 AM)
|
+--------------------------------------------------------------------------
*/

/**
* API: Topic Subscriptions
*
* EXAMPLE USAGE
* <code>
  $api = new api_topic_subscriptions();
  $api->ipsclass =& $this->ipsclass;
  
  // User id to pull tracked topics for
  $api->config['userid'] 		= 1;
  
  // How many to pull, can also set "offset" to define a start point
  $api->config['limit'] 		= 10;
  
  // Can be any column in ibf_topics (t) or ibf_tracker (tr)
  $api->config['order_field']	= 't.last_post';
  
  $topics = $api->return_topic_subscriptions();
  // Loop on $topics and output the data, will contain joined ibf_topics and ibf_members data
* </code>
*
* @package		InvisionPowerBoard
* @subpackage	APIs
* @author		Brandon Farber
* @copyright	Invision Power Services, Inc.
* @version		2.2
*/

if ( ! defined( 'IPS_API_PATH' ) )
{
	/**
	* Define classes path
	*/
	define( 'IPS_API_PATH', dirname(__FILE__) ? dirname(__FILE__) : '.' );
}

if ( ! class_exists( 'api_core' ) )
{
	require_once( IPS_API_PATH.'/api_core.php' );
}

/**
* API: Topic Subscriptions
*
* This class deals with all available topic subscription (ibf_tracker) functions.
*
* @package		InvisionPowerBoard
* @subpackage	APIs
* @author  	 	Matt Mecham
* @version		2.2
* @since		2.3
*/
class api_topic_subscriptions extends api_core
{
	/**
	* IPS Class Object
	*
	* @var object
	*/
	var $ipsclass;
	
	/**
	* Config
	*
	* @var array
	*/
	var $config = array( 'offset'		=> 0,
						 'limit'		=> 10,
						 'userid'		=> 0,
						 'order_field' => 't.last_post',
						 'order_by'    => 'DESC' );
									
	/*-------------------------------------------------------------------------*/
	// Returns an array of tracker and topic data
	/*-------------------------------------------------------------------------*/
	/**
	* Returns an array of topic data
	*
	* @return   array	Array of topic data
	*/
	
	function return_topic_subscriptions()
	{
		//----------------------------------
		// Init the config vars
		//----------------------------------
		
		$return_array = array();

		if( !$this->config['userid'] OR intval($this->config['userid']) <= 0 )
		{
			return array();
		}
		
		if( !$this->config['order_field'] )
		{
			$this->config['order_field'] = 't.last_post';
		}
		
		if( !in_array( strtoupper($this->config['order_by']), array( 'DESC', 'ASC' ) ) )
		{
			$this->config['order_by'] = 'DESC';
		}
	
		//----------------------------------
		// Get the appropriate data
		//----------------------------------
	
		$this->ipsclass->DB->build_query( array( 'select'	=> 'tr.*',
												 'from'		=> array( 'tracker' => 'tr' ),
												 'where'	=> 'tr.member_id=' . intval($this->config['userid']),
												 'order'	=> $this->config['order_field'] . ' ' . $this->config['order_by'],
												 'limit'	=> array( intval($this->config['offset']), intval($this->config['limit']) ),
												 'add_join'	=> array(
												 					array( 'select'	=> 't.*',
												 							'from'	=> array( 'topics' => 't' ),
												 							'where'	=> 't.tid=tr.topic_id',
												 							'type'	=> 'left'
												 						),
												 					array( 'select'	=> 'm.members_display_name',
												 							'from'	=> array( 'members' => 'm' ),
												 							'where'	=> 'm.id=t.last_poster_id',
												 							'type'	=> 'left'
												 						)
												 					)
										)		);
		$outer = $this->ipsclass->DB->exec_query();
		
		if( !$this->ipsclass->DB->get_num_rows($outer) )
		{
			return array();
		}
		else
		{
			while( $r = $this->ipsclass->DB->fetch_row($outer) )
			{
				$return_array[] = $r;
			}
		}
		
		return $return_array;
	}
	
}
?>