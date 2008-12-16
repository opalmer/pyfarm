<?php

/*
+---------------------------------------------------------------------------
|   Invision Power Board
|   ========================================
|   by Matthew Mecham
|   (c) 2006 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
+---------------------------------------------------------------------------
|   INVISION POWER BOARD IS NOT FREE SOFTWARE!
|   http://www.invisionboard.com
+---------------------------------------------------------------------------
|   > $Id$
|   > $Revision: 102 $
|   > $Date: 2005-12-22 10:14:15 +0000 (Thu, 22 Dec 2005) $
+---------------------------------------------------------------------------
|
|   > ALLOWED METHODS FILE: IPB
|   > Script written by Matt Mecham
|   > Date started: Monday 25th June 2007 (14:24)
|
+---------------------------------------------------------------------------
*/
												
$ALLOWED_METHODS = array();

$ALLOWED_METHODS['fetchOnlineUsers'] = array(
												   'in'  => array(
																	'api_key'           => 'string',
																	'api_module'        => 'string',
																	'sep_character'     => 'string',
															     ),
												   'out' => array(
																	'response' => 'xmlrpc'
																 )
												 );

$ALLOWED_METHODS['fetchStats'] = array(
												   'in'  => array(
																	'api_key'           => 'string',
																	'api_module'        => 'string',
															     ),
												   'out' => array(
																	'response' => 'xmlrpc'
																 )
												 );
												
$ALLOWED_METHODS['fetchTopics'] = array(
												   'in'  => array(
																	'api_key'           => 'string',
																	'api_module'        => 'string',
																	'forum_ids'    		=> 'string',
																	'order_field'       => 'string',
																	'order_by'       	=> 'string',
																	'offset'       		=> 'integer',
																	'limit'       		=> 'integer',
																	'view_as_guest'     => 'integer',
															     ),
												   'out' => array(
																	'response' => 'xmlrpc'
																 )
												 );
$ALLOWED_METHODS['fetchForums'] = array(
												   'in'  => array(
																	'api_key'           => 'string',
																	'api_module'        => 'string',
																	'forum_ids' 		=> 'string',
																	'view_as_guest'     => 'integer',
															     ),
												   'out' => array(
																	'response' => 'xmlrpc'
																 )
												 );
												
$ALLOWED_METHODS['fetchForumsOptionList'] = array(
												   'in'  => array(
																	'api_key'            => 'string',
																	'api_module'         => 'string',
																	'selected_forum_ids' => 'string',
																	'view_as_guest'      => 'integer',
															     ),
												   'out' => array(
																	'response' => 'xmlrpc'
																 )
												 );
												
$ALLOWED_METHODS['checkMemberExists'] = array(
												   'in'  => array(
																	'api_key'            => 'string',
																	'api_module'         => 'string',
																	'search_type'        => 'string',
																	'search_string'      => 'string',
															     ),
												   'out' => array(
																	'response' => 'xmlrpc'
																 )
												 );

$ALLOWED_METHODS['fetchMember'] = array(
												   'in'  => array(
																	'api_key'            => 'string',
																	'api_module'         => 'string',
																	'search_type'        => 'string',
																	'search_string'      => 'string',
															     ),
												   'out' => array(
																	'response' => 'xmlrpc'
																 )
												 );
												
$ALLOWED_METHODS['postReply'] = array(
												   'in'  => array(
																	'api_key'            => 'string',
																	'api_module'         => 'string',
																	'member_field'       => 'string',
																	'member_key'         => 'string',
																	'topic_id'           => 'integer',
																	'post_content'       => 'string',
															     ),
												   'out' => array(
																	'response' => 'xmlrpc'
																 )
												 );
												
$ALLOWED_METHODS['postTopic'] = array(
												   'in'  => array(
																	'api_key'            => 'string',
																	'api_module'         => 'string',
																	'member_field'       => 'string',
																	'member_key'         => 'string',
																	'forum_id'           => 'integer',
																	'topic_title'		 => 'string',
																	'topic_description'  => 'string',
																	'post_content'       => 'string',
															     ),
												   'out' => array(
																	'response' => 'xmlrpc'
																 )
												 );
												
$ALLOWED_METHODS['helloBoard'] = array(
												   'in'  => array(
																	'api_key'            => 'string',
																	'api_module'         => 'string',
															     ),
												   'out' => array(
																	'response' => 'xmlrpc'
																 )
												 );


?>