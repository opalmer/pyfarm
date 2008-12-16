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
|   > $Date: 2007-10-18 15:44:54 -0400 (Thu, 18 Oct 2007) $
|   > $Revision: 1135 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > FORUMS CLASS
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

class forum_functions
{

	var $forum_cache   = array();
	var $forum_by_id   = array();
	var $forum_built   = array();
	
	var $template_bit  = "";
	var $depth_guide   = "--";
	var $return        = "";
	var $this_forum    = array();
	var $strip_invisible = 0;
	var $mod_cache     = array();
	var $mod_cache_got = 0;
	var $read_topic_only = 0;
	
	/*-------------------------------------------------------------------------*/
	// register_class
	// ------------------
	// Register a $this-> class with this module
	/*-------------------------------------------------------------------------*/
	
	function register_class()
	{
		//NO LONGER NEEDED
	}
	
	
	/*-------------------------------------------------------------------------*/
	// forums_init
	// ------------------
	// Grab all forums and stuff into array
	/*-------------------------------------------------------------------------*/
	
	function forums_init()
	{
		if ( ! is_array( $this->ipsclass->cache['forum_cache'] ) )
		{
			$this->ipsclass->update_forum_cache();
		}
		
		$hide_parents = ',';
		
		$this->forum_cache = array();
		$this->forum_by_id = array();
		
		foreach( $this->ipsclass->cache['forum_cache'] as $f )
		{
			if ( $this->strip_invisible )
			{
				if ( strstr( $hide_parents, ','. $f['parent_id'] .',' ) )
				{
					// Don't show any children of hidden parents
					$hide_parents .= $f['id'].',';
					continue;
				}
				
				
				if ( $f['show_perms'] != '*' )
				{ 
					if ( $this->ipsclass->check_perms($f['show_perms']) != TRUE )
					{
						$hide_parents .= $f['id'].',';
						continue;
					}
				}
			}
			
			if ( $f['parent_id'] < 1 )
			{
				$f['parent_id'] = 'root';
			}
			
			$f['fid'] = $f['id'];
			
			$this->forum_cache[ $f['parent_id'] ][ $f['id'] ] = $f;
			$this->forum_by_id[ $f['id'] ] = &$this->forum_cache[ $f['parent_id'] ][ $f['id'] ];
		}
		
	}
	
	
	function forums_remove_childless_parents()
	{
		foreach ( $this->forum_cache['root'] as $forum_data )
		{
			if ( ! is_array( $this->forum_cache['root'][ $forum_data['id'] ] ) )
			{
				unset( $this->forum_cache['root'][ $forum_data['id'] ] );
				unset( $this->forum_by_id[ $forum_data['id'] ] );
			}
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// forums_get_moderator_cache
	// ------------------
	// Grab all mods innit
	/*-------------------------------------------------------------------------*/
	
	function forums_get_moderator_cache()
	{
		$this->can_see_queued = array();
		
		if ( ! is_array( $this->ipsclass->cache['moderators'] ) )
		{
			$this->ipsclass->cache['moderators'] = array();
			
			require_once( ROOT_PATH.'sources/action_admin/moderator.php' );
			$this->mod           =  new ad_moderator();
			$this->mod->ipsclass =& $this->ipsclass;
			
			$this->mod->rebuild_moderator_cache();
		}
		
		if ( count($this->ipsclass->cache['moderators']) )
		{
			foreach( $this->ipsclass->cache['moderators'] as $r )
			{
				$this->mod_cache[ $r['forum_id'] ][ $r['mid'] ] = array( 'name'  => $r['members_display_name'],
																		 'memid' => $r['member_id'],
																		 'id'    => $r['mid'],
																		 'isg'   => $r['is_group'],
																		 'gname' => $r['group_name'],
																		 'gid'   => $r['group_id'],
																	   );
			}
		}
		
		$this->mod_cache_got = 1;
	}
	
	
	/*-------------------------------------------------------------------------*/
	// forums_get_moderators
	// ------------------
	// Grab all mods innit
	/*-------------------------------------------------------------------------*/
	
	function forums_get_moderators($forum_id="")
	{
		if ( ! $this->mod_cache_got )
		{
			$this->forums_get_moderator_cache();
		}
		
		$mod_string = "";
		
		if ($forum_id == "")
		{
			return "";
		}
		
		if (isset($this->mod_cache[ $forum_id ] ) )
		{
			$mod_string = $this->ipsclass->lang['forum_leader'].' ';
			
			if (is_array($this->mod_cache[ $forum_id ]) )
			{
				foreach ($this->mod_cache[ $forum_id ] as $moderator)
				{
					if ($moderator['isg'] == 1)
					{
						$mod_string .= "<a href='{$this->ipsclass->base_url}act=Members&amp;max_results=30&amp;filter={$moderator['gid']}&amp;sort_order=asc&amp;sort_key=members_display_name&amp;st=0&amp;b=1'>{$moderator['gname']}</a>, ";
					}
					else
					{
						if ( ! $moderator['name'] )
						{
							continue;
						}
						
						$mod_string .= "<a href='{$this->ipsclass->base_url}showuser={$moderator['memid']}'>{$moderator['name']}</a>, ";
					}
				}
				
				$mod_string = preg_replace( "!,\s+$!", "", $mod_string );
				
			}
			else
			{
				if ($moderator['isg'] == 1)
				{
					$mod_string .= "<a href='{$this->ipsclass->base_url}act=Members&amp;max_results=30&amp;filter={$this->mods[$forum_id]['gid']}&amp;sort_order=asc&amp;sort_key=name&amp;st=0&amp;b=1'>{$this->mods[$forum_id]['gname']}</a>, ";
				}
				else
				{
					$mod_string .= "<a href='{$this->ipsclass->base_url}showuser={$this->mods[$forum_id]['memid']}'>{$this->mods[$forum_id]['name']}</a>";
				}
			}
		}
		
		return $mod_string;
		
	}
	
	/*-------------------------------------------------------------------------*/
	// Forums check access
	// ------------------
	// Blah-de-blah
	/*-------------------------------------------------------------------------*/
	
	function forums_check_access($fid, $prompt_login=0, $in='forum', $lofi=0)
	{
		$deny_access = 1;
		
		if ( $this->ipsclass->check_perms( $this->forum_by_id[$fid]['show_perms'] ) == TRUE )
		{
			$this->forum_by_id[$fid]['read_perms'] = isset( $this->forum_by_id[$fid]['read_perms'] ) ? $this->forum_by_id[$fid]['read_perms'] : '';
		
			if ( $this->ipsclass->check_perms( $this->forum_by_id[$fid]['read_perms'] ) == TRUE )
			{
				$deny_access = 0;
			}
			else
			{
				//-----------------------------------------
				// Can see topics?
				//-----------------------------------------
		
				if ( $this->forum_by_id[$fid]['permission_showtopic'] )
				{
					$this->read_topic_only = 1;
					
					if ( $in == 'forum' )
					{
						$deny_access = 0;
					}
					else
					{
						if ( ! $lofi )
						{
							$this->forums_custom_error($fid);
						}
						
						$deny_access = 1;						
					}
				}
				else
				{
					if ( ! $lofi )
					{
						$this->forums_custom_error($fid);
					}
					
					$deny_access = 1;
				}
			}
		}
		else
		{
			if ( ! $lofi )
			{
				$this->forums_custom_error($fid);
			}
			
			$deny_access = 1;
		}
		
		
		//-----------------------------------------
		// Do we have permission to even see the password page?
		//-----------------------------------------
		
		if ( $deny_access == 0 )
		{
			$group_exempt = 0;
			
			if ( isset($this->forum_by_id[$fid]['password']) AND $this->forum_by_id[$fid]['password'] AND $this->forum_by_id[$fid]['sub_can_post'] )
			{
				if ( isset($this->forum_by_id[$fid]['password_override']) )
				{
					if( in_array( $this->ipsclass->member['mgroup'], explode(",", $this->forum_by_id[$fid]['password_override']) ) )
					{
						$group_exempt = 1;
						$deny_access = 0;
					}
				}
				
				if ( $group_exempt == 0 )
				{
					if ( $this->forums_compare_password( $fid ) == TRUE )
					{
						$deny_access = 0;
					}
					else
					{
						$deny_access = 1;
						
						if ( $prompt_login == 1 && !$lofi )
						{
							$this->forums_show_login( $fid );
						}
					}
				}
			}
		}
		
		if ( $deny_access == 1 && ! $lofi )
        {
        	$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission') );
        }
        else if ( $deny_access == 1 && $lofi )
        {
	        return FALSE;
        }
        else
        {
	        return TRUE;
        }
	}
	
	/*-------------------------------------------------------------------------*/
	// Compare forum pasword
	/*-------------------------------------------------------------------------*/
	
	function forums_compare_password( $fid )
	{
		$cookie_pass = $this->ipsclass->my_getcookie( 'ipbforumpass_'.$fid );
		
		if ( trim($cookie_pass) == md5($this->forum_by_id[$fid]['password']) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Get all forums for loaded permission level
	/*-------------------------------------------------------------------------*/
	/**
	* Get all forum IDs we're allowed to view
	*
	* @return	array 	Array of forum ids
	* @since	IPB 2.2.0.2006-08-02
	*/
	function forums_get_all_allowed_forum_ids( $type='show_perms' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$forum_ids = array();
		
		//-----------------------------------------
		// Go get 'em...
		//-----------------------------------------
		
		if ( is_array( $this->forum_by_id ) and count( $this->forum_by_id ) )
		{
			foreach( $this->forum_by_id as $data )
			{
				if ( ! $this->forums_quick_check_access( $data['id'], $type ) )
				{
					$forum_ids[ $data['id'] ] = $data['id'];
				}
			}
		}
		
		//-----------------------------------------
		// Return..
		//-----------------------------------------
		
		return $forum_ids;
	}
	
	/*-------------------------------------------------------------------------*/
	// Quick check
	/*-------------------------------------------------------------------------*/
	
	function forums_quick_check_access( $fid, $type='show_perms' )
	{
		$deny_access = 1;
		
		if ( $this->ipsclass->check_perms( $this->forum_by_id[$fid][ $type ] ) === TRUE )
		{
			$deny_access = 0;
		}
		
		//-----------------------------------------
		// Do we have permission to even see the password page?
		//-----------------------------------------
		
		if ( $deny_access == 0 )
		{
			$group_exempt = 0;
			
			if (isset($this->forum_by_id[$fid]['password']) AND $this->forum_by_id[$fid]['password'])
			{
				if ( isset($this->forum_by_id[$fid]['password_override']) )
				{
					if ( in_array( $this->ipsclass->member['mgroup'], explode(",", $this->forum_by_id[$fid]['password_override']) ) )
					{
						$group_exempt = 1;
						$deny_access  = 0;
					}
				}
				
				if ( $group_exempt == 0 )
				{				
					if ( $this->forums_compare_password( $fid ) == TRUE )
					{
						$deny_access = 0;
					}
					else
					{
						$deny_access = 1;
					}
				}
			}
		}
		
		return $deny_access;
	}
	
	/*-------------------------------------------------------------------------*/
	// Forums custom error
	// ------------------
	// Blah-de-blah
	/*-------------------------------------------------------------------------*/
	
	function forums_custom_error( $fid )
	{
		$tmp = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'permission_custom_error', 'from' => 'forums', 'where' => "id=".$fid) );
		
		if ( $tmp['permission_custom_error'] )
		{
			$this->ipsclass->load_language("lang_error");
			
			if ( ! $this->ipsclass->compiled_templates['skin_global'] )
			{
				$this->ipsclass->load_template('skin_global');
			}
	   
			//-----------------------------------------
			// Update session
			//-----------------------------------------
    	
    		$this->ipsclass->DB->do_shutdown_update( 'sessions', array( 'in_error' => 1 ), "id='{$this->ipsclass->my_session}'" );
			
			list($em_1, $em_2) = explode( '@', $this->ipsclass->vars['email_in'] );
			
			$html  = $this->ipsclass->compiled_templates['skin_global']->Error( $tmp['permission_custom_error'], $em_1, $em_2);
			$print = new display();
			$this->ipsclass->print->add_output($html);
			$this->ipsclass->print->do_output( array('TITLE' => $this->ipsclass->lang['error_title'] ) );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Forums log in screen
	// ------------------
	// Blah-de-blah
	/*-------------------------------------------------------------------------*/
	
	function forums_show_login( $fid )
	{
		if ( ! class_exists( 'skin_forum' ) )
		{
			$this->ipsclass->load_template('skin_forum');
			$this->ipsclass->load_language('lang_forum');
		}
		else
		{
			$this->html = new skin_forum();
		}
		
		/*if (empty($this->ipsclass->member['id']))
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_guests' ) );
		}*/
		
		$this->output = $this->ipsclass->compiled_templates['skin_forum']->forum_password_log_in( $fid );
		
		$this->ipsclass->print->add_output( $this->output );
		
        $this->ipsclass->print->do_output( array( 'TITLE'    => $this->ipsclass->vars['board_name']." -> ".$this->forum_by_id[$fid]['name'],
        					 	  'JS'       => 0,
        					 	  'NAV'      => array( 
        					 	  					   "<a href='".$this->ipsclass->base_url."showforum={$fid}'>{$this->forum_by_id[$fid]['name']}</a>",
        					 	  					 ),
        					  ) );

	}
	
	/*-------------------------------------------------------------------------*/
	// Get parents
	// ------------------
	// Find all the parents of a child without getting the nice lady to 
	// use the superstore tannoy to shout "Small ugly boy in tears at reception"
	/*-------------------------------------------------------------------------*/
	
	function forums_get_parents($root_id, $ids=array())
	{
		// Stop endless loop setting cat as it's own parent?
		if( in_array( $root_id, $ids ) )
		{
			$cnt = 0;
			
			foreach( $ids as $id )
			{
				if( $id == $root_id )
				{
					$cnt++;

					if( $cnt > 1 )
					{
						return $ids;
					}
				}
			}
		}

		if ( $this->forum_by_id[ $root_id ]['parent_id'] and $this->forum_by_id[ $root_id ]['parent_id'] != 'root' )
		{
			$ids[] = $this->forum_by_id[ $root_id ]['parent_id'];
			
			$ids = $this->forums_get_parents( $this->forum_by_id[ $root_id ]['parent_id'], $ids );
		}
		
		return $ids;
	}
	
	/*-------------------------------------------------------------------------*/
	// Gets children (Debug purposes)
	// ------------------
	// Get all meh children
	/*-------------------------------------------------------------------------*/
	
	function forums_get_children( $root_id, $ids=array() )
	{
		if ( isset($this->forum_cache[ $root_id ]) AND is_array( $this->forum_cache[ $root_id ] ) )
		{
			foreach( $this->forum_cache[ $root_id ] as $forum_data )
			{
				$ids[] = $forum_data['id'];
				
				$ids = $this->forums_get_children($forum_data['id'], $ids);
			}
		}
		
		return $ids;
	}
	
	
	/*-------------------------------------------------------------------------*/
	// Calcualte Children
	// ------------------
	// Gets cumulative posts/topics - sets new post marker and last topic id
	/*-------------------------------------------------------------------------*/
	
	function forums_calc_children($root_id, $forum_data=array(), $done_pass=0)
	{
		if ( isset($this->forum_cache[ $root_id ]) AND is_array( $this->forum_cache[ $root_id ] ) )
		{
			foreach( $this->forum_cache[ $root_id ] as $data )
			{
				if ( $data['last_post'] > $forum_data['last_post'] )
				{
					$forum_data['last_post']         = $data['last_post'];
					$forum_data['fid']               = $data['id'];
					$forum_data['last_id']           = $data['last_id'];
					$forum_data['last_title']        = $data['last_title'];
					$forum_data['password']          = isset($data['password']) ? $data['password'] : '';
					$forum_data['password_override'] = isset($data['password_override']) ? $data['password_override'] : '';
					$forum_data['last_poster_id']    = $data['last_poster_id'];
					$forum_data['last_poster_name']  = $data['last_poster_name'];
					
					if( !$forum_data['last_poster_id'] )
					{
						$forum_data['last_poster_name'] = $this->ipsclass->vars['guest_name_pre'] . $forum_data['last_poster_name'] . $this->ipsclass->vars['guest_name_suf'];
					}
					
					# Bug http://forums.invisionpower.com/index.php?autocom=bugtracker&code=show_bug&bug_title_id=3931&bug_cat_id=3
					#$forum_data['status']            = $data['status'];
					
					//-----------------------------------------
					// Is this forum last post > current last post
					// and if so, did we clear the marker more recently for
					// this topic?
					//-----------------------------------------
				
					if ( isset($data['_db_row']['marker_last_cleared']) AND $data['_db_row']['marker_last_cleared'] > $forum_data['_db_row']['marker_last_cleared'] )
					{
						$forum_data['_db_row']['marker_last_cleared'] = $data['_db_row']['marker_last_cleared'];
					}
				}
				
				//-----------------------------------------
				// Sort out forum marker
				//-----------------------------------------
				
				$data['_db_row']['marker_unread'] 		= isset($data['_db_row']['marker_unread']) ? $data['_db_row']['marker_unread'] : 0;
				$forum_data['_db_row']['marker_unread']	= isset($forum_data['_db_row']['marker_unread']) ? $forum_data['_db_row']['marker_unread'] : 0;
				$forum_data['_db_row']['marker_unread'] += $data['_db_row']['marker_unread'];
				
				//-----------------------------------------
				// Figure out if we have markers
				// Takes care of rows not in the DB yet
				//-----------------------------------------
				
				if ( !isset($data['_db_row']['marker_unread']) OR !$data['_db_row']['marker_unread'] )
				{
					$data['_db_row']['marker_last_cleared'] = isset($data['_db_row']['marker_last_cleared']) ? $data['_db_row']['marker_last_cleared'] : 0;
					
					$this->ipsclass->member['members_markers']['board'] = isset($this->ipsclass->member['members_markers']['board']) ? $this->ipsclass->member['members_markers']['board'] : '';
					
					$test_time = $this->ipsclass->member['members_markers']['board'] > $data['_db_row']['marker_last_cleared'] ? $this->ipsclass->member['members_markers']['board'] : $data['_db_row']['marker_last_cleared'];
					
					if ( $data['last_post'] > $test_time )
					{
						$data['_db_row']['marker_unread'] = 1;
					}
				}
				
				//-----------------------------------------
				// Copy last cleared date
				//-----------------------------------------
				
				if ( $data['_db_row']['marker_unread'] )
				{
					$forum_data['_db_row']['marker_last_cleared'] = $data['_db_row']['marker_last_cleared'];
				}
				
				//-----------------------------------------
				// Topics and posts
				//-----------------------------------------
				
				$forum_data['posts']  += $data['posts'];
				$forum_data['topics'] += $data['topics'];
				
				if ( $this->ipsclass->member['g_is_supmod'] or ( isset($this->ipsclass->member['_moderator'][ $data['id'] ]['post_q']) AND $this->ipsclass->member['_moderator'][ $data['id'] ]['post_q'] == 1 ) )
				{
					$forum_data['queued_posts']  += $data['queued_posts'];
					$forum_data['queued_topics'] += $data['queued_topics'];
				}
				
				if ( ! $done_pass )
				{
					$forum_data['subforums'][ $data['id'] ] = $this->ipsclass->compiled_templates['skin_boards']->show_subforum_link($data['id'],$data['name']);
				}
				
				$forum_data = $this->forums_calc_children( $data['id'], $forum_data, 1 );
			}
		}
		
		return $forum_data;
	}
	
	/*-------------------------------------------------------------------------*/
	// Create forum breadcrumb nav
	// ------------------
	// Simple and effective - just like me :(
	/*-------------------------------------------------------------------------*/
	
	function forums_breadcrumb_nav($root_id, $url='showforum=')
	{
		$nav_array[] = "<a href='".$this->ipsclass->base_url."$url{$root_id}'>{$this->forum_by_id[$root_id]['name']}</a>";
	
		$ids = $this->forums_get_parents( $root_id );
		
		if ( is_array($ids) and count($ids) )
		{
			foreach( $ids as $id )
			{
				$data = $this->forum_by_id[$id];
			
				$nav_array[] = "<a href='".$this->ipsclass->base_url."$url{$data['id']}'>{$data['name']}</a>";
			}
		}
		
		return array_reverse($nav_array);
	}
	
	/*-------------------------------------------------------------------------*/
	// forum jumpee
	// ------------------
	// Builds the forum jumpee dunnit
	/*-------------------------------------------------------------------------*/
	
	function forums_forum_jump($html=0, $override=0, $remove_redirects=0)
	{
		$jump_string = "";
		
		if( is_array($this->forum_cache['root']) AND count($this->forum_cache['root']) )
		{
			foreach( $this->forum_cache['root'] as $forum_data )
			{
				if ( $forum_data['sub_can_post'] or ( isset($this->forum_cache[ $forum_data['id'] ]) AND is_array($this->forum_cache[ $forum_data['id'] ]) AND count($this->forum_cache[ $forum_data['id'] ]) ) )
				{
					$forum_data['redirect_on'] = isset($forum_data['redirect_on']) ? $forum_data['redirect_on'] : 0;
					
					if( $remove_redirects == 1 AND $forum_data['redirect_on'] == 1 )
					{
						continue;
					}
					
					$selected = "";
					
					if ($html == 1 or $override == 1)
					{
						if ($this->ipsclass->input['f'] and $this->ipsclass->input['f'] == $forum_data['id'])
						{
							$selected = ' selected="selected"';
						}
					}
					
					$jump_string .= "<option value=\"{$forum_data['id']}\"".$selected.">".$forum_data['name']."</option>\n";
					
					$depth_guide = $this->depth_guide;
					
					if ( array_key_exists( $forum_data['id'], $this->forum_cache ) AND is_array( $this->forum_cache[ $forum_data['id'] ] ) )
					{
						foreach( $this->forum_cache[ $forum_data['id'] ] as $forum_data )
						{
							if( $remove_redirects == 1 AND $forum_data['redirect_on'] == 1 )
							{
								continue;
							}						
							
							if ($html == 1 or $override == 1)
							{
								$selected = "";
								
								if ($this->ipsclass->input['f'] and $this->ipsclass->input['f'] == $forum_data['id'])
								{
									$selected = ' selected="selected"';
								}
							}
							
							$jump_string .= "<option value=\"{$forum_data['id']}\"".$selected.">&nbsp;&nbsp;&#0124;".$depth_guide." ".$forum_data['name']."</option>\n";
							
							if( $this->ipsclass->vars['short_forum_jump'] == 0 OR $override == 1 )
							{
								$jump_string = $this->forums_forum_jump_internal( $forum_data['id'], $jump_string, $depth_guide . $this->depth_guide, $html, $override, $remove_redirects );
							}
						}
					}
				}
			}
		}
		
		return $jump_string;
	}
	
	function forums_forum_jump_internal($root_id, $jump_string="", $depth_guide="",$html=0, $override=0, $remove_redirects=0)
	{
		if ( isset($this->forum_cache[ $root_id ]) AND is_array( $this->forum_cache[ $root_id ] ) )
		{
			foreach( $this->forum_cache[ $root_id ] as $forum_data )
			{
				if( $remove_redirects == 1 AND $forum_data['redirect_on'] == 1 )
				{
					continue;
				}
				
				$selected = "";
								
				if ($html == 1 or $override == 1)
				{
					if ($this->ipsclass->input['f'] and $this->ipsclass->input['f'] == $forum_data['id'])
					{
						$selected = ' selected="selected"';
					}
				}
					
				$jump_string .= "<option value=\"{$forum_data['id']}\"".$selected.">&nbsp;&nbsp;&#0124;".$depth_guide." ".$forum_data['name']."</option>\n";
				
				$jump_string = $this->forums_forum_jump_internal( $forum_data['id'], $jump_string, $depth_guide . $this->depth_guide, $html, $override );
			}
		}
		
		
		return $jump_string;
	}
	
	/*-------------------------------------------------------------------------*/
	// Format Forum
	// ------------------
	// Sorts out last poster, etc
	/*-------------------------------------------------------------------------*/
	
	function forums_format_lastinfo($forum_data)
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$show_subforums = 1;
		$this->ipsclass->input['f']                    = isset($this->ipsclass->input['f']) ? intval($this->ipsclass->input['f']) : 0;
		$this->ipsclass->vars['disable_subforum_show'] = intval( $this->ipsclass->vars['disable_subforum_show'] );
		
		if ( $this->ipsclass->check_perms( $this->forum_by_id[ $forum_data['id'] ]['read_perms'] ) !== TRUE )
		{
			$show_subforums = 0;
		}
		
		$forum_data['img_new_post'] = $this->forums_new_posts($forum_data);
			
		if ( $forum_data['img_new_post'] == '<{C_ON}>' )
		{
			$forum_data['img_new_post'] = $this->ipsclass->compiled_templates['skin_boards']->forum_img_with_link($forum_data['img_new_post'], $forum_data['id']);
		}
		else if ( $forum_data['img_new_post'] == '<{C_ON_CAT}>' )
		{
			$forum_data['img_new_post'] = $this->ipsclass->compiled_templates['skin_boards']->subforum_img_with_link($forum_data['img_new_post'], $forum_data['id']);
		}
		
		$forum_data['last_post'] = $this->ipsclass->get_date($forum_data['last_post'], 'LONG');
					
		$forum_data['last_topic'] = $this->ipsclass->lang['f_none'];
		
		$forum_data['full_last_title'] = isset($forum_data['last_title']) ? $forum_data['last_title'] : '';
		
		if (isset($forum_data['last_title']) and $forum_data['last_id'])
		{
			$forum_data['last_title'] = strip_tags($forum_data['last_title']);
			$forum_data['last_title'] = str_replace( "&#33;" , "!", $forum_data['last_title'] );
			$forum_data['last_title'] = str_replace( "&quot;", "\"", $forum_data['last_title'] );
			
			$forum_data['last_title'] = $this->ipsclass->txt_truncate($forum_data['last_title'], 30);
			
			if ( ( isset($forum_data['password']) AND $forum_data['password'] ) OR ( $this->ipsclass->check_perms($this->forum_by_id[ $forum_data['fid'] ]['read_perms']) != TRUE AND $this->forum_by_id[ $forum_data['fid'] ]['permission_showtopic'] == 0 ) )
			{
				$forum_data['last_topic'] = $this->ipsclass->lang['f_protected'];
			}
			else
			{
				$forum_data['last_unread'] = $this->ipsclass->compiled_templates['skin_boards']->forumrow_lastunread_link($forum_data['id'], $forum_data['last_id']);
				$forum_data['last_topic']  = "<a href='{$this->ipsclass->base_url}showtopic={$forum_data['last_id']}&amp;view=getnewpost' title='{$this->ipsclass->lang['tt_gounread']}: {$forum_data['full_last_title']}'>{$forum_data['last_title']}</a>";
			}
		}
		
						
		if ( isset($forum_data['last_poster_name']))
		{
			$forum_data['last_poster'] = $forum_data['last_poster_id'] ? "<a href='{$this->ipsclass->base_url}showuser={$forum_data['last_poster_id']}'>{$forum_data['last_poster_name']}</a>"
																	   : $this->ipsclass->vars['guest_name_pre'] . $forum_data['last_poster_name'] . $this->ipsclass->vars['guest_name_suf'];
		}
		else
		{
			$forum_data['last_poster'] = $this->ipsclass->lang['f_none'];
		}

		//-----------------------------------------
		// Moderators
		//-----------------------------------------
		
		$forum_data['moderator'] = $this->forums_get_moderators($forum_data['id']);
		
		$forum_data['posts']  = $this->ipsclass->do_number_format($forum_data['posts']);
		$forum_data['topics'] = $this->ipsclass->do_number_format($forum_data['topics']);
		
		if ( $this->ipsclass->vars['disable_subforum_show'] == 0 AND $show_subforums == 1 )
		{
			if ( isset($forum_data['subforums']) and is_array( $forum_data['subforums'] ) and count( $forum_data['subforums'] ) )
			{
				$forum_data['show_subforums'] = $this->ipsclass->compiled_templates['skin_boards']->show_subforum_all_links( implode( ', ', $forum_data['subforums'] ) );
			}
		}
		
		if ( $this->ipsclass->member['g_is_supmod'] or ( isset($this->ipsclass->member['_moderator'][ $forum_data['id'] ]['post_q']) AND $this->ipsclass->member['_moderator'][ $forum_data['id'] ]['post_q'] == 1 ) )
		{
			if ( $forum_data['queued_posts'] or $forum_data['queued_topics'] )
			{
				$forum_data['_queued_img'] = $this->ipsclass->compiled_templates['skin_boards']->show_queued_img( $forum_data['id'] );
				$forum_data['_queued_info'] = $this->ipsclass->compiled_templates['skin_boards']->show_queued_info( intval($forum_data['queued_posts']), intval($forum_data['queued_topics']) );
			}
		}
		
		return $forum_data;
	}
	
	/*-------------------------------------------------------------------------*/
	//
	// Generate the appropriate folder icon for a forum
	//
	/*-------------------------------------------------------------------------*/
	
	function forums_new_posts($forum_data)
	{
		if ( ! $forum_data['status'] )
		{
			return "<{C_LOCKED}>";
		}

		$sub = 0;
        
        if ( isset($forum_data['subforums']) AND count($forum_data['subforums']) )
        {
        	$sub = 1;
        }

        $fid   = $forum_data['fid'] == "" ? $forum_data['id'] : $forum_data['fid'];
        
        # Uncomment if you want to clear markers from the last visit.
        //$rtime = $this->ipsclass->member['last_visit'] > $this->ipsclass->member['members_markers']['board'] ? $this->ipsclass->member['last_visit'] : $this->ipsclass->member['members_markers']['board'];
        
        $this->ipsclass->member['members_markers']['board'] = isset( $this->ipsclass->member['members_markers']['board'])	? $this->ipsclass->member['members_markers']['board'] : ( isset($this->ipsclass->forum_read[ 0 ]) ? $this->ipsclass->forum_read[ 0 ] : 0 );
        $this->ipsclass->forum_read[ $fid ]					= isset($this->ipsclass->forum_read[ $fid ])			? $this->ipsclass->forum_read[ $fid ]				  : 0;
        $forum_data['_db_row']['marker_last_cleared']       = isset($forum_data['_db_row']['marker_last_cleared'])	? $forum_data['_db_row']['marker_last_cleared']       : $this->ipsclass->forum_read[ $fid ];
        
        $rtime = $this->ipsclass->member['members_markers']['board'];
        $ctime = isset($this->ipsclass->forum_read[ $fid ]) ? $this->ipsclass->forum_read[ $fid ] : 0;
        
        $rtime = $rtime > $ctime ? $rtime : $ctime;
       
		//-----------------------------------------
		// Got a last cleared date so we have a db
		// row... and there are no unread topics
		//-----------------------------------------
		
        if ( isset($forum_data['_db_row']) AND is_array( $forum_data['_db_row'] ) )
        {
        	$rtime = $rtime > $forum_data['_db_row']['marker_last_cleared'] ? $rtime : $forum_data['_db_row']['marker_last_cleared'];
        }

        //-----------------------------------------
        // Sub forum?
        //-----------------------------------------
        
        if ($sub == 0)
        {
			$sub_cat_img = '';
        }
        else
        {
        	$sub_cat_img = '_CAT';
        }
        
        if ( isset($forum_data['password']) AND $forum_data['password'] AND $sub == 0 )
        {
            return $forum_data['last_post'] && $forum_data['last_post'] > $rtime ? "<{C_ON_RES}>" : "<{C_OFF_RES}>";
        }
        
        return ( $forum_data['last_post'] && $forum_data['last_post'] > $rtime ) ? "<{C_ON".$sub_cat_img."}>" : "<{C_OFF".$sub_cat_img."}>";
    }

}


?>