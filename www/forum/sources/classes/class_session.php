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
|   > $Date: 2007-10-17 16:29:37 -0400 (Wed, 17 Oct 2007) $
|   > $Revision: 1133 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > SESSION CLASS
|   > Module written by Matt Mecham
|   > Date started: 26th January 2004
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

class session
{
	# Global
	var $ipsclass;
	
    var $ip_address = 0;
    var $user_agent = "";
    
    # Flag
    var $session_recorded = FALSE;
    
    var $time_now          = 0;
    var $session_id        = 0;
    
    var $session_dead_id   = 0;
    var $session_user_id   = 0;
    var $session_user_pass = "";
    
    var $last_click        = 0;
    var $location          = "";
    
    var $member            = array();
	var $botmap            = array();
	
	var $do_update         = 1;
    
    /*-------------------------------------------------------------------------*/
    // Authorise
    /*-------------------------------------------------------------------------*/
    
    function authorise()
    {
    	//-----------------------------------------
        // INIT
        //-----------------------------------------
        
        $this->member     = array( 'id' => 0, 'name' => "", 'members_display_name' => "", 'mgroup' => $this->ipsclass->vars['guest_group'], 'member_forum_markers' => array() );
        $this->time_now   = time();
        $cookie           = array();
        
        //-----------------------------------------
        // Before we go any lets check the load settings..
        //-----------------------------------------
        
        if ($this->ipsclass->vars['load_limit'] > 0)
        {
	        //-----------------------------------------
	        // Check cache
	        //-----------------------------------------
	        
	        if( $this->ipsclass->cache['systemvars']['loadlimit'] )
	        {
		        $loadinfo = explode( "-", $this->ipsclass->cache['systemvars']['loadlimit'] );
		        
		        if ( count($loadinfo) )
		        {
			        //-----------------------------------------
			        // We have cache! $$
			        //-----------------------------------------
			        
        			$this->ipsclass->server_load = $loadinfo[0];
        			
        			if ($this->ipsclass->server_load > $this->ipsclass->vars['load_limit'])
        			{
	        			//---------------------------------------
	        			// Visiting Lo-fi page?
	        			//---------------------------------------
	        			
	        			if( defined( 'LOFI_NAME' ) AND constant( 'LOFI_NAME' ) == 'lofiversion' )
	        			{
		        			$this->ipsclass->boink_it( $this->ipsclass->vars['board_url'] );
	        			}
	        			else
	        			{
        					$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'server_too_busy', 'INIT' => 1 ) );
    					}
        			}
    			}
			}
        }
       
        //-----------------------------------------
		// Are they banned?
		//-----------------------------------------
		
		if ( is_array( $this->ipsclass->cache['banfilters'] ) and count( $this->ipsclass->cache['banfilters'] ) )
		{
			foreach ($this->ipsclass->cache['banfilters'] as $ip)
			{
				$ip = str_replace( '\*', '.*', preg_quote( trim($ip), "/") );
				
				if ( preg_match( "/^$ip$/", $this->ipsclass->ip_address ) )
				{
					$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'you_are_banned', 'INIT' => 1 ) );
				}
			}
		}
        
        //-----------------------------------------
        // Return as guest if running a task
        //-----------------------------------------
        
        if ( $this->ipsclass->input['act'] == 'task' )
        {
        	$this->member = $this->ipsclass->set_up_guest();
        	$this->member['mgroup'] = $this->ipsclass->vars['guest_group'];
        	$this->ipsclass->input['last_activity'] = time();
			$this->ipsclass->input['last_visit']    = time();
			
			return $this->member;
        }
         
        //-----------------------------------------
        // no new headers if we're simply viewing an attachment..
        //-----------------------------------------
        
        if ( $this->ipsclass->input['act'] == 'Attach' or $this->ipsclass->input['act'] == 'Reg' )
        {
        	$this->ipsclass->no_print_header = 1;
        }
        
        //-----------------------------------------
        // no new headers if we're updating chat
        //-----------------------------------------
        
        if ( ($this->ipsclass->input['act'] == 'chat' and $this->ipsclass->input['CODE'] == 'update')
          OR $this->ipsclass->input['act'] == 'xmlout'
          OR $this->ipsclass->input['act'] == 'rssout'
          OR $this->ipsclass->input['act'] == 'attach' )
        {
        	$this->ipsclass->no_print_header = 1;
        	$this->do_update                 = 0;
        }
        
        //-----------------------------------------
        // Manage bots? (tee-hee)
        //-----------------------------------------
        // Gotta love Mac's ;)
        $this->ipsclass->vars['search_engine_bots'] = str_replace( "\r", '', $this->ipsclass->vars['search_engine_bots'] );
        //-----------------------------------------
        
        if ( $this->ipsclass->vars['spider_sense'] == 1 and $this->ipsclass->vars['search_engine_bots'] )
        {
        	foreach( explode( "\n", $this->ipsclass->vars['search_engine_bots'] ) as $bot )
        	{
        		list($ua, $n) = explode( "=", $bot );
        		
        		if ( $ua and $n )
        		{
        			$this->bot_map[ strtolower($ua) ] = $n;
        			$this->bot_safe[] = preg_quote( $ua, "/" );
        		}
        	}
        	
        	if ( preg_match( '/('.implode( '|', $this->bot_safe ) .')/i', $this->ipsclass->my_getenv('HTTP_USER_AGENT'), $match ) )
        	{
        		$this->ipsclass->DB->simple_construct( array( 'select' => '*',
															  'from'   => 'groups',
															  'where'  =>" g_id=".intval($this->ipsclass->vars['spider_group'])
													 )      );
        		$this->ipsclass->DB->simple_exec();
        	
        		$group = $this->ipsclass->DB->fetch_row();
        
				foreach ($group as $k => $v)
				{
					$this->member[ $k ] = $v;
				}
				
				$this->member['mgroup']							= $this->member['g_id'];
				$this->member['restrict_post']    				= 1;
				$this->member['g_use_search']     				= 0;
				$this->member['g_email_friend']   				= 0;
				$this->member['g_edit_profile']   				= 0;
				$this->member['g_use_pm']         				= 0;
				$this->member['g_is_supmod']      				= 0;
				$this->member['g_access_cp']      				= 0;
				$this->member['g_access_offline'] 				= 0;
				$this->member['g_avoid_flood']    				= 0;
				$this->member['id']               				= 0;
				$this->ipsclass->member['_cache'] 				= array();
				$this->ipsclass->member['_cache']['friends'] 	= array();
				
				$this->ipsclass->perm_id       = $this->member['g_perm_id'];
       			$this->ipsclass->perm_id_array = explode( ",", $this->ipsclass->perm_id );
       			$this->ipsclass->session_type  = 'cookie';
       			$this->ipsclass->is_bot        = 1;
       			$this->session_id              = "";
       			
       			$agent = trim($match[0]);
       			$agent = substr( str_replace( '\r', '', $agent ), 0, 40 );
       			
       			//-----------------------------------------
       			// Using lofi?
       			//-----------------------------------------
       			
       			if ( strstr( $this->ipsclass->my_getenv('PHP_SELF'), 'lofiversion' ) )
       			{
       				$qstring = "Lo-Fi: ".str_replace( "/", "", strrchr( $this->ipsclass->my_getenv('PHP_SELF'), "/" ) );
       			}
       			else
       			{
       				$qstring = str_replace( "'", "", $this->ipsclass->my_getenv('QUERY_STRING'));
       			}
       			
       			$qstring = htmlentities( strip_tags( str_replace( '\\', '', $qstring ) ) );
       			
       			if ( $this->ipsclass->vars['spider_visit'] )
       			{
       				$this->ipsclass->DB->do_shutdown_insert( 'spider_logs', array (
																	'bot'          => $agent,
																	'query_string' => $qstring,
																	'ip_address'   => $this->ipsclass->my_getenv('REMOTE_ADDR'),
																	'entry_date'   => time(),
														)        );
       			}
       			
       			if ( $this->ipsclass->vars['spider_active'] )
       			{
       				$this->create_bot_session($agent, $this->bot_map[ strtolower($agent) ]);
       			}
       			
       			return $this->member;
        	}
        }
        
        //-----------------------------------------
        // Continue!
        //-----------------------------------------
        
        $cookie['session_id']   = $this->ipsclass->my_getcookie('session_id');
        $cookie['member_id']    = $this->ipsclass->my_getcookie('member_id');
        $cookie['pass_hash']    = $this->ipsclass->my_getcookie('pass_hash');

        if ( $cookie['session_id'] )
        {
        	$this->get_session($cookie['session_id']);
        	$this->ipsclass->session_type = 'cookie';
        }
        elseif ( isset($this->ipsclass->input['s']) AND $this->ipsclass->input['s'] )
        {
        	$this->get_session($this->ipsclass->input['s']);
        	$this->ipsclass->session_type = 'url';
        }
        else
        {
        	$this->session_id = 0;
        }
        
		//-----------------------------------------
		// Do we have a valid session ID?
		//-----------------------------------------
		
		if ( $this->session_id )
		{
			//-----------------------------------------
			// We've checked the IP addy and browser, so we can assume that this is
			// a valid session.
			//-----------------------------------------
			
			if ( ($this->session_user_id != 0) and ( ! empty($this->session_user_id) ) )
			{
				//-----------------------------------------
				// It's a member session, so load the member.
				//-----------------------------------------
				
				$this->load_member($this->session_user_id);
				
				//-----------------------------------------
				// Did we get a member?
				//-----------------------------------------

				if ( (! $this->member['id']) or ($this->member['id'] == 0) )
				{
					$this->unload_member();
					$this->update_guest_session();
				}
				else
				{
					$this->update_member_session();
				}
			}
			else
			{
				$this->update_guest_session();
			}
		}
		else
		{
			//-----------------------------------------
			// We didn't have a session, or the session didn't validate
			// Do we have cookies stored?
			//-----------------------------------------
			
			if ( $cookie['member_id'] != "" and $cookie['pass_hash'] != "" )
			{
				//-----------------------------------------
				// Load member
				//-----------------------------------------
				
				$this->load_member( $cookie['member_id'] );
				
				//-----------------------------------------
				// INIT log in key stuff
				//-----------------------------------------
				
				$_ok     = 1;
				$_days   = 0;
				$_sticky = 1;
				$_time   = ( $this->ipsclass->vars['login_key_expire'] ) ? ( time() + ( intval($this->ipsclass->vars['login_key_expire']) * 86400 ) ) : 0;
				
				if ( (! $this->member['id']) or ($this->member['id'] == 0) )
				{
					$this->unload_member();
					$this->create_guest_session();
				}
				else
				{
					if ( $this->member['member_login_key'] == $cookie['pass_hash'] )
					{
						//-----------------------------------------
						// STRONG HOLD
						//-----------------------------------------
						
						if ( $this->ipsclass->stronghold_check_cookie( $this->member['id'], $this->member['member_login_key'] ) !== TRUE )
						{
							//-----------------------------------------
							// Create new log in key as stronghold
							// failed
							//-----------------------------------------
							
							$this->member['member_login_key'] = $this->ipsclass->converge->generate_auto_log_in_key();

							$this->ipsclass->DB->do_update( 'members', array( 'member_login_key' 		=> $this->member['member_login_key'],
							 												  'member_login_key_expire' => $_time ), 'id='.$this->member['id'] );
							
							$this->unload_member();
							$this->create_guest_session();
						}
						else
						{
							//-----------------------------------------
							// Key expired?
							//-----------------------------------------
							
							if ( $this->ipsclass->vars['login_key_expire'] )
							{ 
								$_sticky = 0;
								$_days   = $this->ipsclass->vars['login_key_expire'];
								
								if ( time() > $this->member['member_login_key_expire'] )
								{
									$_ok = 0;
								}
							}
							
							if ( $_ok == 1 )
							{
								$this->create_member_session();
							
								//-----------------------------------------
								// Change the log in key to make each authentication
								// use a unique token. This means that if a cookie is
								// stolen, the hacker can only use the auth once.
								//-----------------------------------------
							
								if ( $this->ipsclass->vars['login_change_key'] )
								{
									$this->member['member_login_key'] = $this->ipsclass->converge->generate_auto_log_in_key();

									$this->ipsclass->DB->do_update( 'members', array( 'member_login_key' 		=> $this->member['member_login_key'],
									 												  'member_login_key_expire' => $_time ), 'id='.$this->member['id'] );

									$this->ipsclass->my_setcookie( "pass_hash", $this->member['member_login_key'], $_sticky, $_days );
									
									$this->ipsclass->stronghold_set_cookie( $this->member['id'], $this->member['member_login_key'] );
								}
							}
							else
							{
								$this->unload_member();
								$this->create_guest_session();
							}
						}
					}
					else
					{
						$this->unload_member();
						$this->create_guest_session();
					}
				}
			}
			else
			{
				$this->create_guest_session();
			}
		}
		
		//-----------------------------------------
        // Knock out Google Web Accelerator
        //-----------------------------------------
       
        $http_moz = $this->ipsclass->my_getenv('HTTP_X_MOZ');

        if ( isset($http_moz) AND strstr( strtolower($http_moz), 'prefetch' ) AND $this->member['id'] )
		{
			if ( IPB_PHP_SAPI == 'cgi-fcgi' OR IPB_PHP_SAPI == 'cgi' )
			{
				@header('Status: 403 Forbidden');
			}
			else
			{
				@header('HTTP/1.1 403 Forbidden');
			}
			
			print "Prefetching or precaching is not allowed";
			exit();
		}
		
		//-----------------------------------------
        // Are we a member of several groups?
        //-----------------------------------------
		
        if ( ! $this->member['id'] AND ! $this->ipsclass->is_bot )
        {
        	$this->member                           = $this->ipsclass->set_up_guest();
        	$this->member['mgroup']                 = $this->ipsclass->vars['guest_group'];
        	$this->ipsclass->input['last_activity'] = time();
			$this->ipsclass->input['last_visit']    = time();
        }
		
        //-----------------------------------------
        // Do we have a cache?
        //-----------------------------------------
        
        if ( ! is_array( $this->ipsclass->cache['group_cache'] ) )
		{
			$this->ipsclass->cache['group_cache'] = array();
			
			$this->ipsclass->DB->simple_construct( array( 'select' => "*",
														  'from'   => 'groups'
												 )      );
			
			$this->ipsclass->DB->simple_exec();
			
			while ( $i = $this->ipsclass->DB->fetch_row() )
			{
				$this->ipsclass->cache['group_cache'][ $i['g_id'] ] = $i;
			}
			
			$this->ipsclass->update_cache( array( 'name' => 'group_cache', 'array' => 1, 'deletefirst' => 1 ) );
		}
		
		//-----------------------------------------
        // Set up main 'display' group
        //-----------------------------------------
        
        $this->member = array_merge( $this->member, $this->ipsclass->cache['group_cache'][ $this->member['mgroup'] ] );
		
		//-----------------------------------------
        // Are we a member of several groups?
        //-----------------------------------------
		
		$this->build_group_permissions();
		
		//-----------------------------------------
		// Are we a mod?
		//-----------------------------------------
		
		if ( $this->member['mgroup'] != $this->ipsclass->vars['guest_group'] )
		{
			//-----------------------------------------
			// Sprinkle on some moderator stuff...
			//-----------------------------------------
		
			if ( $this->member['g_is_supmod'] == 1 )
			{
				$this->member['is_mod'] = 1;
			}
			else if ( is_array($this->ipsclass->cache['moderators']) AND count($this->ipsclass->cache['moderators']) )
			{
				$other_mgroups = array();
				
				if ( $this->member['mgroup_others'] )
				{
					$other_mgroups = explode( ",", $this->ipsclass->clean_perm_string( $this->member['mgroup_others'] ) );
				}
				 
				foreach( $this->ipsclass->cache['moderators'] as $r )
				{
					if ( $r['member_id'] == $this->member['id'] )
					{
						$this->member['_moderator'][ $r['forum_id'] ] = $r;
						$this->member['is_mod'] = 1;
					}
					else if( $r['group_id'] == $this->member['mgroup'] )
					{
						// Individual mods override group mod settings
						// If array is set, don't override it
						
						if( !is_array($this->member['_moderator'][ $r['forum_id'] ]) OR !count($this->member['_moderator'][ $r['forum_id'] ]) )
						{
							$this->member['_moderator'][ $r['forum_id'] ] = $r;
						}
						
						$this->member['is_mod'] = 1;
					}
					else if( count($other_mgroups) AND in_array( $r['group_id'], $other_mgroups ) )
					{
						// Individual mods override group mod settings
						// If array is set, don't override it
						
						if( !is_array($this->member['_moderator'][ $r['forum_id'] ]) OR !count($this->member['_moderator'][ $r['forum_id'] ]) )
						{
							$this->member['_moderator'][ $r['forum_id'] ] = $r;
						}
						
						$this->member['is_mod'] = 1;
					}						
				}
			}
			
			//-----------------------------------------
			// Forum markers
			//-----------------------------------------
			
			$this->member['members_markers'] = unserialize(stripslashes( $this->member['members_markers'] ) );
		}
		
		//header('content-type:text/plain'); print_r($this->member); exit();
		
        //-----------------------------------------
        // Synchronise the last visit and activity times if
        // we have some in the member profile
        //-----------------------------------------
        
        if ( $this->member['id'] )
        {
        	if ( !isset($this->ipsclass->input['last_activity']) OR !$this->ipsclass->input['last_activity'] )
        	{
        		$this->ipsclass->input['last_activity'] = $this->member['last_activity'] ? $this->member['last_activity'] : $this->time_now;
        	}
        	
        	if ( !isset($this->ipsclass->input['last_visit']) OR !$this->ipsclass->input['last_visit'] )
        	{
				$this->ipsclass->input['last_visit'] = $this->member['last_visit'] ? $this->member['last_visit'] : $this->time_now;
        	}
        
			//-----------------------------------------
			// If there hasn't been a cookie update in 2 hours,
			// we assume that they've gone and come back
			//-----------------------------------------

			if ( ! $this->member['last_visit'] )
			{
				//-----------------------------------------
				// No last visit set, do so now!
				//-----------------------------------------
				
				$this->ipsclass->DB->simple_construct( array( 'update' => 'members',
															  'set'    => "last_visit=".$this->time_now.", last_activity=".$this->time_now,
															  'where'  => "id=".$this->member['id']
													 )      );
				
				$this->ipsclass->DB->simple_shutdown_exec();
				
			}
			else if ( (time() - $this->ipsclass->input['last_activity']) > 300 )
			{
				//-----------------------------------------
				// If the last click was longer than 5 mins ago and this is a member
				// Update their profile.
				//-----------------------------------------
				
				list( $be_anon, $loggedin ) = explode( '&', $this->member['login_anonymous'] );
				
				$this->ipsclass->DB->do_shutdown_update( 'members', array( 'login_anonymous' => "{$be_anon}&1", 'last_activity' => $this->time_now ), 'id=' . $this->member['id'] );
			}
			
			//-----------------------------------------
			// Check ban status
			//-----------------------------------------
			
			if ( $this->member['temp_ban'] )
			{
				$ban_arr = $this->ipsclass->hdl_ban_line(  $this->member['temp_ban'] );
				
				if ( time() >= $ban_arr['date_end'] )
				{
					//-----------------------------------------
					// Update this member's profile
					//-----------------------------------------
					
					$this->ipsclass->DB->simple_construct( array( 'update' => 'members',
																  'set'    => "temp_ban=''",
																  'where'  => "id=".$this->member['id']
														 )      );
					
					$this->ipsclass->DB->simple_shutdown_exec();
				}
				else
				{
					$this->ipsclass->member = $this->member;
					$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'account_susp', 'INIT' => 1, 'EXTRA' => $this->ipsclass->get_date($ban_arr['date_end'],'LONG', 1) ) );
				}
			}
		}
		
		//-----------------------------------------
        // Set a session ID cookie
        //-----------------------------------------

        $this->ipsclass->my_setcookie("session_id", $this->session_id, -1);
        
        return $this->member;
    }
    
    /*-------------------------------------------------------------------------*/
    // Build group permissions
    /*-------------------------------------------------------------------------*/
    
    function build_group_permissions()
    {
    	if ( isset($this->member['mgroup_others']) AND $this->member['mgroup_others'] )
		{
			$groups_id    = explode( ',', $this->member['mgroup_others'] );
			$exclude      = array( 'g_title', 'g_icon', 'prefix', 'suffix', 'g_promotion', 'g_photo_max_vars' );
			$less_is_more = array( 'g_search_flood' );
			$zero_is_best = array( 'g_attach_max', 'g_attach_per_post', 'g_edit_cutoff', 'g_max_messages' );
			
			# Blog
			$zero_is_best = array_merge( $zero_is_best, array( 'g_blog_attach_max', 'g_blog_attach_per_entry', 'g_blog_preventpublish' ) );
			
			# Gallery
			$zero_is_best = array_merge( $zero_is_best, array( 'g_max_diskspace', 'g_max_upload', 'g_max_transfer', 'g_max_views', 'g_album_limit', 'g_img_album_limit', 'g_movie_size' ) );
			
			if ( count( $groups_id ) )
			{
				foreach( $groups_id as $pid )
				{
					if ( ! isset($this->ipsclass->cache['group_cache'][ $pid ]['g_id']) OR !$this->ipsclass->cache['group_cache'][ $pid ]['g_id'] )
					{
						continue;
					}
					
					//-----------------------------------------
					// Loop through and mix
					//-----------------------------------------
					
					foreach( $this->ipsclass->cache['group_cache'][ $pid ] as $k => $v )
					{
						if ( ! in_array( $k, $exclude ) )
						{
							//-----------------------------------------
							// Add to perm id list
							//-----------------------------------------
							
							if ( $k == 'g_perm_id' )
							{
								$this->member['g_perm_id'] .= ','.$v;
							}
							else if ( in_array( $k, $zero_is_best ) )
							{
								if ( $this->member[ $k ] == 0 )
								{
									continue;
								}
								else if( $v == 0 )
								{
									$this->member[ $k ] = 0;
								}
								else if ( $v > $this->member[ $k ] )
								{
									$this->member[ $k ] = $v;
								}
							}							
							else if ( in_array( $k, $less_is_more ) )
							{
								if ( $v < $this->member[ $k ] )
								{
									$this->member[ $k ] = $v;
								}
							}
							else
							{
								if ( $v > $this->member[ $k ] )
								{
									$this->member[ $k ] = $v;
								}
							}
						}
					}
				}
			}

			//-----------------------------------------
			// Tidy perms_id
			//-----------------------------------------
			
			$rmp = array();
			$tmp = explode( ',', $this->ipsclass->clean_perm_string($this->member['g_perm_id']) );
			
			if ( count( $tmp ) )
			{
				foreach( $tmp as $t )
				{
					$rmp[ $t ] = $t;
				}
			}
			
			if ( count( $rmp ) )
			{
				$this->member['g_perm_id'] = implode( ',', $rmp );
			}
		}
		
		$this->ipsclass->perm_id       = ( isset($this->member['org_perm_id']) AND $this->member['org_perm_id'] ) ? $this->member['org_perm_id'] : $this->member['g_perm_id'];
        
        $this->ipsclass->perm_id_array = explode( ",", $this->ipsclass->perm_id );
    }
    
    /*-------------------------------------------------------------------------*/
	// Attempt to load a member
	/*-------------------------------------------------------------------------*/
	
    function load_member($member_id=0)
    {
    	$member_id = intval($member_id);
    	
     	if ($member_id != 0)
        {
            $this->ipsclass->DB->build_query( array( 'select' => "id, name, mgroup, member_login_key, member_login_key_expire, email, restrict_post, view_sigs, view_avs, view_pop, view_img, auto_track,
																  mod_posts, language, skin, new_msg, show_popup, msg_total, time_offset, posts, joined, last_post, subs_pkg_chosen,
																  ignored_users, login_anonymous, last_visit, last_activity, dst_in_use, view_prefs, org_perm_id, mgroup_others, temp_ban, sub_end,
																  has_blog, has_gallery, members_markers, members_editor_choice, members_auto_dst, members_display_name, members_created_remote,
																  members_cache, members_disable_pm",
													 'from'   => 'members',
													 'where'  => 'id='.$member_id ) );
													 
			$this->ipsclass->DB->exec_query();
			
            if ( $this->ipsclass->DB->get_num_rows() )
            {
            	$this->member = $this->ipsclass->DB->fetch_row();
            }
            
            //-----------------------------------------
            // Unless they have a member id, log 'em in as a guest
            //-----------------------------------------
            
            if ( ($this->member['id'] == 0) or (empty($this->member['id'])) )
            {
				$this->unload_member();
            }
		}
		
		//-----------------------------------------
		// Unpack cache
		//-----------------------------------------
		
		if ( isset($this->member['members_cache']) )
		{
			$this->member['_cache'] = $this->ipsclass->unpack_member_cache( $this->member['members_cache'] );
		}
		else
		{
			$this->member['_cache'] = array();
		}
		
		if ( ! isset( $this->member['_cache']['friends'] ) or ! is_array( $this->member['_cache']['friends'] ) )
		{
			$this->member['_cache']['friends'] = array();
		}
		
		unset($member_id);
	}
	
	/*-------------------------------------------------------------------------*/
	// Remove the users cookies
	/*-------------------------------------------------------------------------*/
	
	function unload_member()
	{
		$this->ipsclass->my_setcookie( "member_id" , "0", -1  );
		$this->ipsclass->my_setcookie( "pass_hash" , "0", -1  );
		
		$this->member['id']       				= 0;
		$this->member['name']     				= "";
		$this->member['members_display_name']   = "";
	}
    
    /*-------------------------------------------------------------------------*/
    // Updates a current session.
    /*-------------------------------------------------------------------------*/
    
    function update_member_session()
    {
        //-----------------------------------------
        // Make sure we have a session id.
        //-----------------------------------------
        
        if ( ! $this->session_id )
        {
        	$this->create_member_session();
        	return;
        }
        
        if ( empty($this->member['id']) )
        {
        	$this->unload_member();
        	$this->create_guest_session();
        	return;
        }
        
        if ( time() - $this->member['last_activity'] > $this->ipsclass->vars['session_expiration'] )
        {
	        // Session is expired - create new session
	        
	        $this->create_member_session();
	        return;
        }
        
        //-----------------------------------------
        // Get module settings
        //-----------------------------------------
        
        $vars = $this->_get_location_settings();
        
        //-----------------------------------------
        // Still update?
        //-----------------------------------------
         
        if ( ! $this->do_update )
        {
        	return;
        }
        
        $this->ipsclass->my_setcookie( "pass_hash", $this->member['member_login_key'], ( $this->ipsclass->vars['login_key_expire'] ? 0 : 1 ), $this->ipsclass->vars['login_key_expire'] );
        
        $this->ipsclass->DB->force_data_type = array( 'member_name' => 'string' );
        
        $this->ipsclass->DB->do_shutdown_update( 'sessions',
												 array(
														'member_name'        => $this->member['members_display_name'],
														'member_id'          => intval($this->member['id']),
														'member_group'       => $this->member['mgroup'],
														'login_type'         => intval(substr($this->member['login_anonymous'],0, 1)),
														'running_time'       => $this->time_now,
														'location'           => $vars['location'],
														'in_error'           => 0,
														'location_1_type'    => isset($vars['1_type']) ? $vars['1_type'] : '',
														'location_1_id'      => isset($vars['1_id']) ? intval($vars['1_id']) : 0,
														'location_2_type'    => isset($vars['2_type']) ? $vars['2_type'] : '',
														'location_2_id'      => isset($vars['2_id']) ? intval($vars['2_id']) : 0,
														'location_3_type'    => isset($vars['3_type']) ? $vars['3_type'] : '',
														'location_3_id'      => isset($vars['3_id']) ? intval($vars['3_id']) : 0,
													  ),
												"id='{$this->session_id}'"
							  					);

		$this->ipsclass->DB->do_shutdown_update( 'members', array( 'member_login_key_expire' => time() + ( $this->ipsclass->vars['login_key_expire'] * 86400 ) ), "id={$this->member['id']}" );
    }        
    
    /*-------------------------------------------------------------------------*/
    // Update guest session
    /*-------------------------------------------------------------------------*/
    
    function update_guest_session()
    {
        //-----------------------------------------
        // Make sure we have a session id.
        //-----------------------------------------
        
        if ( ! $this->session_id )
        {
        	$this->create_guest_session();
        	return;
        }
        
        //-----------------------------------------
        // Get module settings
        //-----------------------------------------
        
        $vars = $this->_get_location_settings();
        
        //-----------------------------------------
        // Still update?
        //-----------------------------------------
         
        if ( ! $this->do_update )
        {
        	return;
        }
        
        $this->ipsclass->DB->force_data_type = array( 'member_name' => 'string' );
        
        $this->ipsclass->DB->do_shutdown_update( 'sessions',
												 array(
														'member_name'        => '',
														'member_id'          => 0,
														'member_group'       => $this->ipsclass->vars['guest_group'],
														'login_type'         => 0,
														'running_time'       => $this->time_now,
														'location'           => $vars['location'],
														'in_error'           => 0,
														'location_1_type'    => isset($vars['1_type']) ? $vars['1_type'] : '',
														'location_1_id'      => isset($vars['1_id']) ? intval($vars['1_id']) : 0,
														'location_2_type'    => isset($vars['2_type']) ? $vars['2_type'] : '',
														'location_2_id'      => isset($vars['2_id']) ? intval($vars['2_id']) : 0,
														'location_3_type'    => isset($vars['3_type']) ? $vars['3_type'] : '',
														'location_3_id'      => isset($vars['3_id']) ? intval($vars['3_id']) : 0,
													  ),
												"id='{$this->session_id}'"
											  );
    } 
                    
    
    /*-------------------------------------------------------------------------*/
    // Get a session based on the current session ID
    /*-------------------------------------------------------------------------*/
    
    function get_session($session_id="")
    {
		//-----------------------------------------
		// INIT
    	//-----------------------------------------
    	
        $result     = array();
        $query      = "";
        $session_id = preg_replace("/([^a-zA-Z0-9])/", "", $session_id);
        
        if ( $session_id )
        {
			if ( $this->ipsclass->vars['match_browser'] == 1 )
			{
				$query = " AND browser='". substr( $this->ipsclass->user_agent, 0, 200 ) ."'";
			}
			
			if ( $this->ipsclass->vars['match_ipaddress'] == 1 )
			{
				$query .= " AND ip_address='".$this->ipsclass->ip_address."'";
			}
			
			$this->ipsclass->DB->simple_construct( array( 'select' => 'id, member_id, running_time, location',
														  'from'   => 'sessions',
														  'where'  => "id='".$session_id."'".$query
												 )      );
				
			$this->ipsclass->DB->simple_exec();
			
			if ( $this->ipsclass->DB->get_num_rows() != 1 )
			{
				//-----------------------------------------
				// Either there is no session, or we have more than one session..
				//-----------------------------------------
				
				$this->session_dead_id   = $session_id;
				$this->session_id        = 0;
        		$this->session_user_id   = 0;
        		return;
			}
			else
			{
				$result = $this->ipsclass->DB->fetch_row();
				
				if ($result['id'] == "")
				{
					$this->session_dead_id   = $session_id;
					$this->session_id        = 0;
					$this->session_user_id   = 0;
					unset($result);
					return;
				}
				else
				{
					$this->session_id        = $result['id'];
					$this->session_user_id   = $result['member_id'];
					$this->last_click        = $result['running_time'];
        			$this->location          = $result['location'];
        			unset($result);
					return;
				}
			}
		}
    }
    
    /*-------------------------------------------------------------------------*/
    //
    // Creates a member session.
    //
    /*-------------------------------------------------------------------------*/
    
    function create_member_session()
    {
        if ($this->member['id'])
        {
        	//-----------------------------------------
        	// Remove the defunct sessions
        	//-----------------------------------------
        	
			//$this->ipsclass->vars['session_expiration'] = $this->ipsclass->vars['session_expiration'] ? (time() - $this->ipsclass->vars['session_expiration']) : (time() - 3600);
			
			$this->ipsclass->DB->do_delete( 'sessions', "member_id=".$this->member['id'] );
			
			$this->session_id  = md5( uniqid(microtime()) );
			
			//-----------------------------------------
			// Get module settings
			//-----------------------------------------
			
			$vars = $this->_get_location_settings();
        	
        	//-----------------------------------------
			// Still update?
			//-----------------------------------------
			 
			if ( ! $this->do_update )
			{
				return;
			}
        
			//-----------------------------------------
        	// Insert the new session
        	//-----------------------------------------
        	
        	$this->ipsclass->DB->force_data_type = array( 'member_name' => 'string' );
        	
        	$this->ipsclass->DB->do_shutdown_insert( 'sessions',
													 array(
															'id'                 => $this->session_id,
															'member_name'        => $this->member['members_display_name'],
															'member_id'          => intval($this->member['id']),
															'member_group'       => $this->member['mgroup'],
															'login_type'         => intval(substr($this->member['login_anonymous'],0, 1)),
															'running_time'       => $this->time_now,
															'ip_address'         => $this->ipsclass->ip_address,
															'browser'            => substr( $this->ipsclass->user_agent, 0, 200 ),
															'location'           => $vars['location'],
															'in_error'           => 0,
															'location_1_type'    => isset($vars['1_type']) ? $vars['1_type'] : '',
															'location_1_id'      => isset($vars['1_id']) ? intval($vars['1_id']) : 0,
															'location_2_type'    => isset($vars['2_type']) ? $vars['2_type'] : '',
															'location_2_id'      => isset($vars['2_id']) ? intval($vars['2_id']) : 0,
															'location_3_type'    => isset($vars['3_type']) ? $vars['3_type'] : '',
															'location_3_id'      => isset($vars['3_id']) ? intval($vars['3_id']) : 0,
														  )
												  );
			
			//-----------------------------------------
			// If this is a member, update their last visit times, etc.
			//-----------------------------------------

			if ( time() - $this->member['last_activity'] > $this->ipsclass->vars['session_expiration'] )
			{
				//-----------------------------------------
				// Reset the topics read cookie..
				//-----------------------------------------
				
				$this->ipsclass->my_setcookie('topicsread', '');
				
				list( $be_anon, $loggedin ) = explode( '&', $this->member['login_anonymous'] );
				
				$this->ipsclass->DB->simple_construct( array( 'update' => 'members',
															  'set'    => "login_anonymous='$be_anon&1', last_visit=last_activity, last_activity=".$this->time_now,
															  'where'  => "id=".$this->member['id']
													 )      );
					
				$this->ipsclass->DB->simple_shutdown_exec();
				
				//-----------------------------------------
				// Fix up the last visit/activity times.
				//-----------------------------------------
				
				$this->ipsclass->input['last_visit']    = $this->member['last_activity'];
				$this->ipsclass->input['last_activity'] = $this->time_now;
			}
			
        	$this->ipsclass->my_setcookie( "pass_hash", $this->member['member_login_key'], ( $this->ipsclass->vars['login_key_expire'] ? 0 : 1 ), $this->ipsclass->vars['login_key_expire'] );
        
			$this->ipsclass->DB->do_shutdown_update( 'members', array( 'member_login_key_expire' => time() + ( $this->ipsclass->vars['login_key_expire'] * 86400 ) ), "id={$this->member['id']}" );			
		}
		else
		{
			$this->create_guest_session();
		}
    }
    
    /*-------------------------------------------------------------------------*/
    //
    // Create guest session
    //
    /*-------------------------------------------------------------------------*/
    
    function create_guest_session()
    {
		//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	$query = array();
    	
    	//$this->ipsclass->vars['session_expiration'] = $this->ipsclass->vars['session_expiration'] ? (time() - $this->ipsclass->vars['session_expiration']) : (time() - 3600);
		
		//-----------------------------------------
		// Remove the defunct sessions
		//-----------------------------------------
		
		if ( ($this->session_dead_id != 0) and ( ! empty($this->session_dead_id) ) )
		{
			$query[] = "id='".$this->session_dead_id."'";
		}
		
		if ( $this->ipsclass->vars['match_ipaddress'] == 1 )
		{
			$query[] = "ip_address='".$this->ipsclass->ip_address."'";
		}
		
		if ( count( $query ) )
		{
			$this->ipsclass->DB->simple_construct( array( 'delete' => 'sessions', 'where'  => implode( " OR ", $query ) ) );
			$this->ipsclass->DB->simple_exec();
		}
								 
		$this->session_id  = md5( uniqid(microtime()) );
		
		//-----------------------------------------
        // Get module settings
        //-----------------------------------------
        
        $vars = $this->_get_location_settings();
        
        //-----------------------------------------
        // Still update?
        //-----------------------------------------
         
        if ( ! $this->do_update )
        {
        	return;
        }
        
		//-----------------------------------------
		// Insert the new session
		//-----------------------------------------
		
		$this->ipsclass->DB->force_data_type = array( 'member_name' => 'string' );
		
		$this->ipsclass->DB->do_shutdown_insert( 'sessions',
												 array(
														'id'                 => $this->session_id,
														'member_name'        => '',
														'member_id'          => 0,
														'member_group'       => $this->ipsclass->vars['guest_group'],
														'login_type'         => 0,
														'running_time'       => $this->time_now,
														'ip_address'         => $this->ipsclass->ip_address,
														'browser'            => substr( $this->ipsclass->user_agent, 0, 200 ),
														'location'           => $vars['location'],
														'in_error'           => 0,
														'location_1_type'    => isset($vars['1_type']) ? $vars['1_type'] : '',
														'location_1_id'      => isset($vars['1_id']) ? intval($vars['1_id']) : 0,
														'location_2_type'    => isset($vars['2_type']) ? $vars['2_type'] : '',
														'location_2_id'      => isset($vars['2_id']) ? intval($vars['2_id']) : 0,
														'location_3_type'    => isset($vars['3_type']) ? $vars['3_type'] : '',
														'location_3_id'      => isset($vars['3_id']) ? intval($vars['3_id']) : 0,
													  )
											  );
    }
    
    /*-------------------------------------------------------------------------*/
    // Creates a BOT session
    /*-------------------------------------------------------------------------*/
    
    function create_bot_session($bot, $name="")
    {
    	//-----------------------------------------
        // Get module settings
        //-----------------------------------------
        
        $vars 	= $this->_get_location_settings();
        $sid	= $bot . '=' . str_replace( '.', '', $this->ipsclass->ip_address ) . '_session';
        
        $this->ipsclass->DB->force_data_type = array( 'member_name' => 'string' );
        
        $this->ipsclass->DB->do_replace_into( 'sessions',
												 array(
														'id'                 => $sid,
														'member_name'        => $name ? $name : $bot,
														'member_id'          => 0,
														'member_group'       => $this->ipsclass->vars['spider_group'],
														'login_type'         => intval($this->ipsclass->vars['spider_anon']),
														'running_time'       => $this->time_now,
														'ip_address'         => $this->ipsclass->ip_address,
														'browser'            => substr( $this->ipsclass->user_agent, 0, 200 ),
														'location'           => $vars['location'],
														'in_error'           => 0,
														'location_1_type'    => isset($vars['1_type']) ? $vars['1_type'] : '',
														'location_1_id'      => isset($vars['1_id']) ? intval($vars['1_id']) : 0,
														'location_2_type'    => isset($vars['2_type']) ? $vars['2_type'] : '',
														'location_2_id'      => isset($vars['2_id']) ? intval($vars['2_id']) : 0,
														'location_3_type'    => isset($vars['3_type']) ? $vars['3_type'] : '',
														'location_3_id'      => isset($vars['3_id']) ? intval($vars['3_id']) : 0,
													  ), array( 'id' )
											  );
    }
    
    /*-------------------------------------------------------------------------*/
    // Updates a BOT current session.
    /*-------------------------------------------------------------------------*/
    
    function update_bot_session($bot, $name="")
    {
    	//-----------------------------------------
        // Get module settings
        //-----------------------------------------
        
        $vars = $this->_get_location_settings();
        
        $this->ipsclass->DB->force_data_type = array( 'member_name' => 'string' );
        
		$this->ipsclass->DB->do_shutdown_update( 'sessions',
												 array(
														'member_name'        => $name ? $name : $bot,
														'member_id'          => 0,
														'member_group'       => $this->ipsclass->vars['spider_group'],
														'login_type'         => intval($this->ipsclass->vars['spider_anon']),
														'running_time'       => $this->time_now,
														'location'           => $vars['location'],
														'in_error'           => 0,
														'location_1_type'    => isset($vars['1_type']) ? $vars['1_type'] : '',
														'location_1_id'      => isset($vars['1_id']) ? intval($vars['1_id']) : 0,
														'location_2_type'    => isset($vars['2_type']) ? $vars['2_type'] : '',
														'location_2_id'      => isset($vars['2_id']) ? intval($vars['2_id']) : 0,
														'location_3_type'    => isset($vars['3_type']) ? $vars['3_type'] : '',
														'location_3_id'      => isset($vars['3_id']) ? intval($vars['3_id']) : 0,
													  ),
												  "id='".$bot.'='.str_replace('.','',$this->ipsclass->ip_address )."_session'"
											  );
    }
    
    /*-------------------------------------------------------------------------*/
    // Returns a "clean" query string
    /*-------------------------------------------------------------------------*/
    
    function _get_location_settings()
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	$return = array();
    	$module = isset($this->ipsclass->input['automodule']) ? $this->ipsclass->input['automodule'] : ( isset($this->ipsclass->input['module']) ? $this->ipsclass->input['module'] : '' );
    	
    	//-----------------------------------------
    	// MODULE?
    	//-----------------------------------------
    	
    	if ( $module )
    	{
    		$filename = ROOT_PATH.'sources/components_location/'.$this->ipsclass->txt_alphanumerical_clean( $module ).'.php';
				
			if ( file_exists( $filename ) )
			{
				require_once( $filename );
				$toload           = 'components_location_' . $module;
				$loader           = new $toload;
				$loader->ipsclass =& $this->ipsclass;
				
				$return = $loader->get_session_variables();
				$return['location'] = 'mod:' . $module;
			}
    	}
    	
    	//-----------------------------------------
    	// FORUM
    	//-----------------------------------------
    	
    	else if ( $this->ipsclass->input['act'] == 'sf' AND ( isset($this->ipsclass->input['f']) AND $this->ipsclass->input['f'] ) )
    	{
    		$return = array( 'location' => 'sf',
    						 '2_type'   => 'forum',
    						 '2_id'     => intval($this->ipsclass->input['f']) );
    	}
    	
    	//-----------------------------------------
    	// TOPIC
    	//-----------------------------------------
    	
    	else if ( $this->ipsclass->input['act'] == 'st' AND ( isset($this->ipsclass->input['t']) AND $this->ipsclass->input['t'] ) )
    	{
    		$return = array( 'location' => 'st',
    						 '1_type'   => 'topic',
    						 '1_id'     => intval($this->ipsclass->input['t']),
    						 '2_type'   => 'forum',
    						 '2_id'     => intval($this->ipsclass->input['f']) );
    	}
    	
    	//-----------------------------------------
    	// POST
    	//-----------------------------------------
    	
    	else if ( $this->ipsclass->input['act'] == 'post' AND ( isset($this->ipsclass->input['f']) AND $this->ipsclass->input['f'] ) )
    	{
    		$return = array( 'location' => 'post',
    			 			 '1_type'   => 'topic',
    						 '1_id'     => intval($this->ipsclass->input['t']),
    						 '2_type'   => 'forum',
    						 '2_id'     => intval($this->ipsclass->input['f']) );
    	}
    	
    	//-----------------------------------------
    	// OTHER
    	//-----------------------------------------
    	
    	else
    	{
	    	$this->ipsclass->input['p'] 	= isset($this->ipsclass->input['p']) ? $this->ipsclass->input['p'] : '';
	    	$this->ipsclass->input['CODE']	= isset($this->ipsclass->input['CODE']) ? $this->ipsclass->input['CODE'] : '';
	    	
    		$return = array( 'location' => $this->ipsclass->input['act'].",".$this->ipsclass->input['p'].",".$this->ipsclass->input['CODE'] );
    	}
    	
    	return $return;
    }
    
        
}

?>