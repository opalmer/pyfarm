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
|   > IPB XML-RPC: SERVER FUNCTIONS
|   > Script written by Matt Mecham
|   > Date started: Monday 25th June 2007 (19:36)
|
+---------------------------------------------------------------------------
*/

class API_Server
{
   /**
    * Defines the service for WSDL
    * @access Private
    * @var array
    */			
	var $__dispatch_map = array();
	
   /**
    * IPS Global Class
    * @access Private
    * @var object
    */
	var $ipsclass;
	
	/**
	* IPS API SERVER Class
    * @access Private
    * @var object
    */
	var $class_api_server;
	
	/**
	 * API_Server::API_Server()
	 *
	 * CONSTRUCTOR
	 * 
	 * @return void
	 **/		
	function API_Server( $ipsclass ) 
    {
		//-----------------------------------------
		// Set IPS CLASS
		//-----------------------------------------
		
		$this->ipsclass =& $ipsclass;
		
    	//-----------------------------------------
    	// Load allowed methods and build dispatch
		// list
    	//-----------------------------------------
    	
		require_once( ROOT_PATH . 'interface/board/modules/ipb/methods.php' );
		
		if ( is_array( $ALLOWED_METHODS ) and count( $ALLOWED_METHODS ) )
		{
			foreach( $ALLOWED_METHODS as $_method => $_data )
			{
				$this->__dispatch_map[ $_method ] = $_data;
			}
		}
	}
	
	/**
	 * API_Server::fetchStats()
	 *
	 * Returns details about the board
	 * 
	 * @param  string  $auth_key  	Authentication Key
	 * @param  string  $api_module  Module
	 * @return xml
	 **/	
	function fetchOnlineUsers( $api_key, $api_module, $sep_character=',' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$api_key                = $this->ipsclass->txt_md5_clean( $api_key );
		$api_module             = $this->ipsclass->parse_clean_value( $api_module );
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $api_key, $api_module, 'fetchOnlineUsers' ) !== FALSE )
		{
			$cut_off = $this->ipsclass->vars['au_cutoff'] * 60;
			$time    = time() - $cut_off;
			$rows    = array();
			$ar_time = time();
			
			$this->ipsclass->DB->simple_construct( array( 'select' => 'id, member_id, member_name, login_type, running_time, member_group',
														  'from'   => 'sessions',
														  'where'  => "running_time > $time" ) );
			
			
			$this->ipsclass->DB->simple_exec();
			
			//-----------------------------------------
			// FETCH...
			//-----------------------------------------
			
			while ( $r = $this->ipsclass->DB->fetch_row() )
			{
				$rows[ $r['running_time'].'.'.$r['id'] ] = $r;
			}
			
			krsort( $rows );
			
			//-----------------------------------------
			// Is this a root admin in disguise?
			// Is that kinda like a diamond in the rough?
			//-----------------------------------------
						
			$our_mgroups = array();
			
			if ( isset($this->ipsclass->member['mgroup_others']) AND $this->ipsclass->member['mgroup_others'] )
			{
				$our_mgroups = explode( ",", $this->ipsclass->clean_perm_string( $this->ipsclass->member['mgroup_others'] ) );
			}
			
			$our_mgroups[] = $this->ipsclass->member['mgroup'];
			
			//-----------------------------------------
			// cache all printed members so we
			// don't double print them
			//-----------------------------------------
			
			$cached = array();
			
			foreach ( $rows as $result )
			{
				$last_date = $this->ipsclass->get_time( $result['running_time'] );
				
				//-----------------------------------------
				// Bot?
				//-----------------------------------------
				
				if ( strstr( $result['id'], '_session' ) )
				{
					//-----------------------------------------
					// Seen bot of this type yet?
					//-----------------------------------------
					
					$botname = preg_replace( '/^(.+?)=/', "\\1", $result['id'] );
					
					if ( ! $cached[ $result['member_name'] ] )
					{
						if ( $this->ipsclass->vars['spider_anon'] )
						{
							if ( in_array( $this->ipsclass->vars['admin_group'], $our_mgroups )  )
							{
								$active['member_names'] .= "{$result['member_name']}*{$sep_character} \n";
							}
						}
						else
						{
							$active['member_names'] .= "{$result['member_name']}{$sep_character} \n";
						}
						
						$cached[ $result['member_name'] ] = 1;
					}
					else
					{
						//-----------------------------------------
						// Yup, count others as guest
						//-----------------------------------------
						
						$active['guest_count']++;
					}
				}
				
				//-----------------------------------------
				// Guest?
				//-----------------------------------------
				
				else if ( ! $result['member_id'] )
				{
					$active['guest_count']++;
				}
				
				//-----------------------------------------
				// Member?
				//-----------------------------------------
				
				else
				{
					if ( empty( $cached[ $result['member_id'] ] ) )
					{
						$cached[ $result['member_id'] ] = 1;

						$result['member_name'] = $this->ipsclass->make_name_formatted( $result['member_name'], $result['member_group'] );
						
						if ($result['login_type'])
						{
							if ( (in_array( $this->ipsclass->vars['admin_group'], $our_mgroups )) and ($this->ipsclass->vars['disable_admin_anon'] != 1) )
							{
								$active['member_names'] .= "<a href='{$this->ipsclass->base_url}showuser={$result['member_id']}' title='$last_date'>{$result['member_name']}</a>*{$sep_character} \n";
								$active['anon_count']++;
							}
							else
							{
								$active['anon_count']++;
							}
						}
						else
						{
							$active['member_count']++;
							$active['member_names'] .= "<a href='{$this->ipsclass->base_url}showuser={$result['member_id']}' title='$last_date'>{$result['member_name']}</a>{$sep_character} \n";
						}
					}
				}
			}
			
			$active['member_names'] = preg_replace( "/".preg_quote($sep_character)."$/", "", trim($active['member_names']) );
			
			$active['total_count'] = $active['member_count'] + $active['guest_count'] + $active['anon_count'];
			
			//-----------------------------------------
			// Return info
			//-----------------------------------------
			
			$this->class_api_server->api_send_reply( $active );
			exit();
		}
	}
	
	/**
	 * API_Server::fetchStats()
	 *
	 * Returns details about the board
	 * 
	 * @param  string  $auth_key  	Authentication Key
	 * @param  string  $api_module  Module
	 * @return xml
	 **/	
	function fetchStats( $api_key, $api_module )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$api_key                = $this->ipsclass->txt_md5_clean( $api_key );
		$api_module             = $this->ipsclass->parse_clean_value( $api_module );
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $api_key, $api_module, 'fetchStats' ) !== FALSE )
		{
			if ( ! is_array( $this->ipsclass->cache['stats'] ) )
			{
				$this->ipsclass->cache['stats'] = array();

				$this->ipsclass->update_cache( array( 'name' => 'stats', 'array' => 1, 'deletefirst' => 1 ) );
			}

			$stats =& $this->ipsclass->cache['stats'];

			$most_time     = $this->ipsclass->get_date( $stats['most_date'], 'LONG' );
			$most_count    = $this->ipsclass->do_number_format( $stats['most_count'] );
			
			$total_posts   = $stats['total_topics'] + $stats['total_replies'];
			
			$total_posts   = $this->ipsclass->do_number_format($total_posts);
			$mem_count     = $this->ipsclass->do_number_format($stats['mem_count']);
			$mem_last_id   = $stats['last_mem_id'];
			$mem_last_name = $stats['last_mem_name'];
			
			//-----------------------------------------
			// Return info
			//-----------------------------------------
			
			$this->class_api_server->api_send_reply( array( 'users_most_online'         => $most_count,
			 												'users_most_date_formatted' => $most_time,
															'users_most_data_unix'		=> $stats['most_date'],
															'total_posts'				=> $total_posts,
															'total_members'				=> $mem_count,
															'last_member_id'			=> $mem_last_id,
															'last_member_name'			=> $mem_last_name ) );
			exit();
		}
	}
	
	/**
	 * API_Server::helloBoard()
	 *
	 * Returns details about the board
	 * 
	 * @param  string  $auth_key  	Authentication Key
	 * @param  string  $api_module  Module
	 * @return xml
	 **/	
	function helloBoard( $api_key, $api_module )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$api_key                = $this->ipsclass->txt_md5_clean( $api_key );
		$api_module             = $this->ipsclass->parse_clean_value( $api_module );
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $api_key, $api_module, 'helloBoard' ) !== FALSE )
		{
			//-----------------------------------------
	   		// Upgrade history?
	   		//-----------------------------------------

	   		$latest_version = array( 'upgrade_version_id' => NULL );

	   		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'upgrade_history', 'order' => 'upgrade_version_id DESC', 'limit' => array(1) ) );
	   		$this->ipsclass->DB->simple_exec();

	   		while( $r = $this->ipsclass->DB->fetch_row() )
	   		{
				$latest_version = $r;
	   		}
	
			//-----------------------------------------
			// Return info
			//-----------------------------------------
			
			$this->class_api_server->api_send_reply( array( 'board_name'  		  => $this->ipsclass->vars['board_name'],
			 												'upload_url'  		  => $this->ipsclass->vars['upload_url'],
			 												'ipb_img_url' 		  => $this->ipsclass->vars['ipb_img_url'],
			 												'board_human_version' => $latest_version['upgrade_version_human'],
															'board_long_version'  => ( isset($latest_version['upgrade_notes']) AND $latest_version['upgrade_notes'] ) ? $latest_version['upgrade_notes'] : $this->ipsclass->vn_full ) );
			
			exit();
		}
	}
	
	/**
	 * API_Server::postTopic()
	 *
	 * Returns a member
	 * 
	 * @param  string  $auth_key  	Authentication Key
	 * @param  string  $api_module  Module
	 * @return xml
	 **/	
	function postTopic( $api_key, $api_module, $member_field, $member_key, $forum_id, $topic_title, $topic_description, $post_content )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$api_key                = $this->ipsclass->txt_md5_clean( $api_key );
		$api_module             = $this->ipsclass->parse_clean_value( $api_module );
		$member_field           = $this->ipsclass->parse_clean_value( $member_field );
		$member_key             = $this->ipsclass->parse_clean_value( $member_key );
		$topic_title            = $this->ipsclass->parse_clean_value( $topic_title );
		$topic_description      = $this->ipsclass->parse_clean_value( $topic_description );
		$forum_id			    = intval( $forum_id );
		$UNCLEANED_post_content = $post_content;
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $api_key, $api_module, 'postTopic' ) !== FALSE )
		{
			//-----------------------------------------
			// Add log
			//-----------------------------------------
			
			if ( $this->ipsclass->vars['xmlrpc_log_type'] != 'failed' )
			{
				$this->ipsclass->DB->do_insert( 'api_log', array(   'api_log_key'     => $api_key,
																	'api_log_ip'      => $_SERVER['REMOTE_ADDR'],
																	'api_log_date'    => time(),
																	'api_log_query'   => $this->class_api_server->raw_request,
																	'api_log_allowed' => 1 ) );
			}
			
			//-----------------------------------------
			// Get API classes
			//-----------------------------------------

			$this->__load_api_classes( 'api_topics_and_posts' );
			
			//-----------------------------------------
			// Member field...
			//-----------------------------------------
			
			switch( $member_field )
			{
				case 'email':
				case 'emailaddress':
				case 'email_address':
					$this->api->set_author_by_email( $this->ipsclass->clean_email( strtolower( $member_key ) ) );
				break;
				case 'id':
					$this->api->set_author_by_id( intval( $member_key ) );
				break;
				case 'name':
				case 'username':
				case 'loginname':
					$this->api->set_author_by_name( $member_key );
				break;
				case 'displayname':
				case 'members_display_name':
					$this->api->set_author_by_display_name( $member_key );
				break;
			}
			
			//-----------------------------------------
			// Got a member?
			//-----------------------------------------
			
			if ( ! $this->api->author['id'] )
			{
				$this->class_api_server->api_send_reply( array( 'result'      => 'fail',
																'faultCode'   => '10',
																'faultString' => "IP.Board could not locate a member using $member_key / $member_field" ) );
			}
			
			//-----------------------------------------
			// Try setting the topic...
			//-----------------------------------------
			
			$this->api->set_forum_id( $forum_id );
			
			if ( ! $this->api->forum['id'] )
			{
				$this->class_api_server->api_send_reply( array( 'result'      => 'fail',
																'faultCode'   => '11',
																'faultString' => "IP.Board could not locate a forum using ID $topic_id" ) );
			}
			
			//-----------------------------------------
			// Try setting the post, topic and desc...
			//-----------------------------------------
			
			$this->api->set_post_content( $UNCLEANED_post_content );
			$this->api->set_topic_title( $topic_title );
			$this->api->set_topic_description( $topic_description );
			
			//-----------------------------------------
			// Ok, do it!
			//-----------------------------------------
			
			if ( $this->api->create_new_topic() !== FALSE )
			{
				$this->class_api_server->api_send_reply( array( 'result'   => 'success',
																'topic_id' => $this->api->topic['tid'] ) );
			}
			else
			{
				$this->class_api_server->api_send_reply( array( 'result' => 'fail' ) );
			}
			
			exit();
		}
	}
	
	/**
	 * API_Server::postReply()
	 *
	 * Returns a member
	 * 
	 * @param  string  $auth_key  	Authentication Key
	 * @param  string  $api_module  Module
	 * @return xml
	 **/	
	function postReply( $api_key, $api_module, $member_field, $member_key, $topic_id, $post_content )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$api_key                = $this->ipsclass->txt_md5_clean( $api_key );
		$api_module             = $this->ipsclass->parse_clean_value( $api_module );
		$member_field           = $this->ipsclass->parse_clean_value( $member_field );
		$member_key             = $this->ipsclass->parse_clean_value( $member_key );
		$topic_id			    = intval( $topic_id );
		$UNCLEANED_post_content = $post_content;
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $api_key, $api_module, 'postReply' ) !== FALSE )
		{
			//-----------------------------------------
			// Add log
			//-----------------------------------------
			
			if ( $this->ipsclass->vars['xmlrpc_log_type'] != 'failed' )
			{
				$this->ipsclass->DB->do_insert( 'api_log', array(   'api_log_key'     => $api_key,
																	'api_log_ip'      => $_SERVER['REMOTE_ADDR'],
																	'api_log_date'    => time(),
																	'api_log_query'   => $this->class_api_server->raw_request,
																	'api_log_allowed' => 1 ) );
			}
			
			//-----------------------------------------
			// Get API classes
			//-----------------------------------------

			$this->__load_api_classes( 'api_topics_and_posts' );
			
			//-----------------------------------------
			// Member field...
			//-----------------------------------------
			
			switch( $member_field )
			{
				case 'email':
				case 'emailaddress':
				case 'email_address':
					$this->api->set_author_by_email( $this->ipsclass->clean_email( strtolower( $member_key ) ) );
				break;
				case 'id':
					$this->api->set_author_by_id( intval( $member_key ) );
				break;
				case 'name':
				case 'username':
				case 'loginname':
					$this->api->set_author_by_name( $member_key );
				break;
				case 'displayname':
				case 'members_display_name':
					$this->api->set_author_by_display_name( $member_key );
				break;
			}
			
			//-----------------------------------------
			// Got a member?
			//-----------------------------------------
			
			if ( ! $this->api->author['id'] )
			{
				$this->class_api_server->api_send_reply( array( 'result'      => 'fail',
																'faultCode'   => '10',
																'faultString' => "IP.Board could not locate a member using $member_key / $member_field" ) );
			}
			
			//-----------------------------------------
			// Try setting the topic...
			//-----------------------------------------
			
			$this->api->set_topic_id( $topic_id );
			
			if ( ! $this->api->topic['tid'] )
			{
				$this->class_api_server->api_send_reply( array( 'result'      => 'fail',
																'faultCode'   => '11',
																'faultString' => "IP.Board could not locate a topic using ID $topic_id" ) );
			}
			
			//-----------------------------------------
			// Try setting the post content...
			//-----------------------------------------
			
			$this->api->set_post_content( $UNCLEANED_post_content );
			
			//-----------------------------------------
			// Ok, do it!
			//-----------------------------------------
			
			if ( $this->api->create_new_reply() !== FALSE )
			{
				$this->class_api_server->api_send_reply( array( 'result' => 'success' ) );
			}
			else
			{
				$this->class_api_server->api_send_reply( array( 'result' => 'fail' ) );
			}
			
			exit();
		}
	}
	
	/**
	 * API_Server::fetchMember()
	 *
	 * Returns a member
	 * 
	 * @param  string  $auth_key  	Authentication Key
	 * @param  string  $api_module  Module
	 * @return xml
	 **/	
	function fetchMember( $api_key, $api_module, $search_type, $search_string )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$api_key                = $this->ipsclass->txt_md5_clean( $api_key );
		$api_module             = $this->ipsclass->parse_clean_value( $api_module );
		$search_type            = $this->ipsclass->parse_clean_value( $search_type );
		$search_string          = $this->ipsclass->parse_clean_value( $search_string );
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $api_key, $api_module, 'fetchMember' ) !== FALSE )
		{
			//-----------------------------------------
			// Add log
			//-----------------------------------------
			
			if ( $this->ipsclass->vars['xmlrpc_log_type'] != 'failed' )
			{
				$this->ipsclass->DB->do_insert( 'api_log', array(   'api_log_key'     => $api_key,
																	'api_log_ip'      => $_SERVER['REMOTE_ADDR'],
																	'api_log_date'    => time(),
																	'api_log_query'   => $this->class_api_server->raw_request,
																	'api_log_allowed' => 1 ) );
			}
			
			//-----------------------------------------
			// Get API classes
			//-----------------------------------------

			$this->__load_api_classes( 'api_member' );
			
			//-----------------------------------------
			// Fetch forum list
			//-----------------------------------------
			
			$member = $this->api->get_member( $search_type, $search_string );
			
			if ( ! $member['id'] )
			{
				$member = array( 'id' => 0 );
			}
			
			//-----------------------------------------
			// Return the data
			//-----------------------------------------
		
			$this->class_api_server->api_send_reply( $member );
			exit();
		}
	}
	
	/**
	 * API_Server::checkMemberExists()
	 *
	 * Returns topics
	 * 
	 * @param  string  $auth_key  	Authentication Key
	 * @param  string  $api_module  Module
	 * @return xml
	 **/	
	function checkMemberExists( $api_key, $api_module, $search_type, $search_string )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$api_key                = $this->ipsclass->txt_md5_clean( $api_key );
		$api_module             = $this->ipsclass->parse_clean_value( $api_module );
		$search_type            = $this->ipsclass->parse_clean_value( $search_type );
		$search_string          = $this->ipsclass->parse_clean_value( $search_string );
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $api_key, $api_module, 'checkMemberExists' ) !== FALSE )
		{
			//-----------------------------------------
			// Add log
			//-----------------------------------------
			
			if ( $this->ipsclass->vars['xmlrpc_log_type'] != 'failed' )
			{
				$this->ipsclass->DB->do_insert( 'api_log', array(   'api_log_key'     => $api_key,
																	'api_log_ip'      => $_SERVER['REMOTE_ADDR'],
																	'api_log_date'    => time(),
																	'api_log_query'   => $this->class_api_server->raw_request,
																	'api_log_allowed' => 1 ) );
			}
			
			//-----------------------------------------
			// Get API classes
			//-----------------------------------------

			$this->__load_api_classes( 'api_member' );
			
			//-----------------------------------------
			// Fetch forum list
			//-----------------------------------------
			
			$check  = $this->api->check_for_member( $search_type, $search_string );
			$_check = ( $check === TRUE ) ? 'true' : 'false';
			
			//-----------------------------------------
			// Return the data
			//-----------------------------------------
		
			$this->class_api_server->api_send_reply( array( 'memberExists' => $_check ) );
			exit();
		}
	}
	
	/**
	 * API_Server::fetchForumsOptionList()
	 *
	 * Returns topics
	 * 
	 * @param  string  $auth_key  	Authentication Key
	 * @param  string  $api_module  Module
	 * @return xml
	 **/	
	function fetchForumsOptionList( $api_key, $api_module, $selected_forum_ids, $view_as_guest )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$api_key                = $this->ipsclass->txt_md5_clean( $api_key );
		$api_module             = $this->ipsclass->parse_clean_value( $api_module );
		$selected_forum_ids     = ( $selected_forum_ids ) ? explode( ',', $this->ipsclass->parse_clean_value( $selected_forum_ids ) ) : null;
		$view_as_guest          = intval( $view_as_guest );
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $api_key, $api_module, 'fetchForumsOptionList' ) !== FALSE )
		{
			//-----------------------------------------
			// Add log
			//-----------------------------------------
			
			if ( $this->ipsclass->vars['xmlrpc_log_type'] != 'failed' )
			{
				$this->ipsclass->DB->do_insert( 'api_log', array(   'api_log_key'     => $api_key,
																	'api_log_ip'      => $_SERVER['REMOTE_ADDR'],
																	'api_log_date'    => time(),
																	'api_log_query'   => $this->class_api_server->raw_request,
																	'api_log_allowed' => 1 ) );
			}
			
			//-----------------------------------------
			// Get API classes
			//-----------------------------------------

			$this->__load_api_classes( 'api_forums' );
			
			//-----------------------------------------
			// Fetch forum list
			//-----------------------------------------
			
			$list = $this->api->return_forum_jump_option_list( $selected_forum_ids, $view_as_guest );
			
			//-----------------------------------------
			// Return the data
			//-----------------------------------------
		
			$this->class_api_server->api_send_reply( array( 'forumList' => $list ) );
			exit();
		}
	}
	
	/**
	 * API_Server::fetchForums()
	 *
	 * Returns topics
	 * 
	 * @param  string  $auth_key  	Authentication Key
	 * @param  string  $api_module  Module
	 * @return xml
	 **/	
	function fetchForums( $api_key, $api_module, $forum_ids, $view_as_guest )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$api_key       = $this->ipsclass->txt_md5_clean( $api_key );
		$api_module    = $this->ipsclass->parse_clean_value( $api_module );
		$forum_ids     = ( $forum_ids ) ? explode( ',', $this->ipsclass->parse_clean_value( $forum_ids ) ) : null;
		$view_as_guest = intval( $view_as_guest );
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $api_key, $api_module, 'fetchForums' ) !== FALSE )
		{
			//-----------------------------------------
			// Add log
			//-----------------------------------------
			
			if ( $this->ipsclass->vars['xmlrpc_log_type'] != 'failed' )
			{
				$this->ipsclass->DB->do_insert( 'api_log', array(   'api_log_key'     => $api_key,
																	'api_log_ip'      => $_SERVER['REMOTE_ADDR'],
																	'api_log_date'    => time(),
																	'api_log_query'   => $this->class_api_server->raw_request,
																	'api_log_allowed' => 1 ) );
			}
			
			//-----------------------------------------
			// Get API classes
			//-----------------------------------------

			$this->__load_api_classes( 'api_forums' );
			
			//-----------------------------------------
			// Fetch forum list
			//-----------------------------------------
			
			$forums = $this->api->return_forum_data( $forum_ids, $view_as_guest );
			
			//-----------------------------------------
			// Return the data
			//-----------------------------------------
		
			$this->class_api_server->api_send_reply( $forums );
			exit();
		}
	}
	
	/**
	 * API_Server::fetchTopics()
	 *
	 * Returns topics
	 * 
	 * @param  string  $auth_key  	Authentication Key
	 * @param  string  $api_module  Module
	 * @return xml
	 **/	
	function fetchTopics( $api_key, $api_module, $forum_ids, $order_field, $order_by, $offset, $limit, $view_as_guest )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$api_key       = $this->ipsclass->txt_md5_clean( $api_key );
		$api_module    = $this->ipsclass->parse_clean_value( $api_module );
		$forum_ids 	   = $this->ipsclass->parse_clean_value( $forum_ids );
		$order_field   = $this->ipsclass->parse_clean_value( $order_field );
		$order_by      = ( strtolower( $order_by ) == 'asc' ) ? 'asc' : 'desc';
		$offset		   = intval( $offset );
		$limit		   = intval( $limit );
		$view_as_guest = intval( $view_as_guest );
		
		//-----------------------------------------
		// Authenticate
		//-----------------------------------------
		
		if ( $this->__authenticate( $api_key, $api_module, 'fetchTopics' ) !== FALSE )
		{
			//-----------------------------------------
			// Add log
			//-----------------------------------------
			
			if ( $this->ipsclass->vars['xmlrpc_log_type'] != 'failed' )
			{
				$this->ipsclass->DB->do_insert( 'api_log', array(   'api_log_key'     => $api_key,
																	'api_log_ip'      => $_SERVER['REMOTE_ADDR'],
																	'api_log_date'    => time(),
																	'api_log_query'   => $this->class_api_server->raw_request,
																	'api_log_allowed' => 1 ) );
			}
			
			//-----------------------------------------
			// Get API classes
			//-----------------------------------------

			$this->__load_api_classes( 'api_topic_view' );
			
			//-----------------------------------------
			// Fetch topic list
			//-----------------------------------------
			
			$this->api->topic_list_config['order_field'] = $order_field;
			$this->api->topic_list_config['order_by']	 = $order_by;
			$this->api->topic_list_config['forums']		 = $forum_ids;
			$this->api->topic_list_config['offset']		 = $offset;
			$this->api->topic_list_config['limit']		 = $limit;
			
			$topics = $this->api->return_topic_list_data( $view_as_guest );
			
			//-----------------------------------------
			// Return the data
			//-----------------------------------------
		
			$this->class_api_server->api_send_reply( $topics );
			exit();
		}
	}
	

	
	/**
	 * API_Server::__create_user_session()
	 *
	 * Has to return at least the member ID, member log in key and session ID
	 *
	 * 
	 * @param  array  $member  	Array of member information
	 * @return array  $session	Session information
	 **/
	function __create_user_session( $member )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$update = array();
		
		//-----------------------------------------
		// Generate a new log in key
		//-----------------------------------------

		if ( $this->ipsclass->vars['login_change_key'] OR ! $member['member_login_key'] )
		{
			$update['member_login_key'] = $this->ipsclass->converge->generate_auto_log_in_key();
		}
		
		//-----------------------------------------
		// Set our privacy status
		//-----------------------------------------
		
		$update['login_anonymous'] = '0&1';
		
		//-----------------------------------------
		// Update member?
		//-----------------------------------------
		
		if ( is_array( $update ) and count( $update ) )
		{
			$this->ipsclass->DB->do_update( 'members', $update, 'id=' . $member['id'] );
		}
		
		//-----------------------------------------
		// Still here? Create a new session
		//-----------------------------------------
		
		require_once( ROOT_PATH . 'sources/classes/class_session.php' );
		$session           =  new session();
		$session->ipsclass =& $this->ipsclass;
		$session->time_now =  time();
		$session->member   =  $member;
		
		$session->create_member_session();
		
		$session->member['_session_id'] = $session->session_id;
		
		return $session->member;
	}
	
	/**
	 * API_Server::__create_user_account()
	 *
	 * Routine to create a local user account
	 *
	 * 
	 * @param  string  $email_address  	   Email address of user logged in
	 * @param  string  $md5_once_password  The plain text password, hashed once
	 * @param  string  $ip_address  	   IP Address of registree
	 * @param  string  $unix_join_date     The member's join date in unix format
	 * @param  string  $timezone     	   The member's timezone
	 * @param  string  $dst_autocorrect    The member's DST autocorrect settings
	 * @return array   $member			   Newly created member array
	 **/
	function __create_user_account( $email_address='', $md5_once_password, $ip_address, $unix_join_date, $timezone=0, $dst_autocorrect=0 )
	{
		//-----------------------------------------
		// Check to make sure there's not already
		// a member registered.
		//-----------------------------------------
		
		$member = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																	'from'   => 'members',
																	'where'  => "email='" . $this->ipsclass->DB->add_slashes( $email_address ) . "'" ) );
		
		if ( $member['id'] )
		{
			return $member;
		}
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$unix_join_date = $unix_join_date ? $unix_join_date : time();
		$ip_address     = $ip_address     ? $ip_address     : $this->ipsclass->ip_address;
		
		//-----------------------------------------
		// Grab module..
		//-----------------------------------------
		
		require( ROOT_PATH . "sources/loginauth/login_core.php" );
		$login_core           = new login_core();
		$login_core->ipsclass = $this->ipsclass;
		
		//-----------------------------------------
		// Create member
		//-----------------------------------------
 		
		$member = $login_core->_create_local_member( $email_address, $md5_once_password, $email_address, $unix_join_date, $ip_address );
		
		return $member;
	}
	
	/**
	 * API_Server::__authenticate()
	 *
	 * Checks to see if the request is allowed
	 * 
	 * @param  string $api_key    		Authenticate Key
	 * @param  string $api_module   	Module
	 * @param  string $api_function     Function 
	 * @return string         Error message, if any
	 **/
	function __authenticate( $api_key, $api_module, $api_function )
	{
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( $this->api_user['api_user_id'] )
		{
			$this->api_user['_permissions'] = unserialize( stripslashes( $this->api_user['api_user_perms'] ) );
			
			if ( $this->api_user['_permissions'][ $api_module ][ $api_function ] == 1 )
			{
				return TRUE;
			}
			else
			{
				$this->ipsclass->DB->do_insert( 'api_log', array(   'api_log_key'     => $api_key,
																	'api_log_ip'      => $_SERVER['REMOTE_ADDR'],
																	'api_log_date'    => time(),
																	'api_log_query'   => $this->class_api_server->raw_request,
																	'api_log_allowed' => 0 ) );
				
				$this->class_api_server->api_send_reply( array( 'faultCode'   => '200',
																'faultString' => "API Key {$api_key} does not have permission for {$api_module}/{$api_function}" ) );

				return FALSE;
			}
		}
		else
		{
			$this->ipsclass->DB->do_insert( 'api_log', array(   'api_log_key'     => $api_key,
																'api_log_ip'      => $_SERVER['REMOTE_ADDR'],
																'api_log_date'    => time(),
																'api_log_query'   => $this->class_api_server->raw_request,
																'api_log_allowed' => 0 ) );
			
			$this->class_api_server->api_send_reply( array( 'faultCode'   => '100',
															'faultString' => "API Key {$api_key} does not have permission for {$api_module}/{$api_function}" ) );
																																						
			return FALSE;
		}
	}
	
	/**
	 * API_Server::__load_api_classes()
	 *
	 * Loads the API system classes
	 * 
	 * @param  string $api_class	The name of the API class to load
	 * @access Private	 
	 * @return void
	 **/
	function __load_api_classes( $api_class )
	{
		require_once( ROOT_PATH . "sources/api/api_core.php" );
		require_once( ROOT_PATH . "sources/api/" . $api_class . '.php' );
		$this->api              =  new $api_class;
		$this->api->ipsclass    =& $this->ipsclass;
		$this->api->path_to_ipb = ROOT_PATH;
		$this->api->api_init();
	}
}
?>