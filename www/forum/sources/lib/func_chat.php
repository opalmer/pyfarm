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
|   > $Date: 2006-12-13 16:15:40 -0500 (Wed, 13 Dec 2006) $
|   > $Revision: 791 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > IPChat functions
|   > Script written by Matt Mecham
|   > Date started: 29th September 2003
|
+--------------------------------------------------------------------------
*/


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}


class func_chat
{

	var $class  = "";
	var $server = "";
	var $html   = "";
	
	function func_chat()
	{
		$this->server = str_replace( 'http://', '', $this->ipsclass->vars['chat04_whodat_server_addr'] );
	}
	
	//-----------------------------------------
	// register_class($class)
	//
	// Register a $this-> with this class 
	//
	//-----------------------------------------
	
	function register_class()
	{
		// NO LONGER NEEDED
	}

	//-----------------------------------------
	// Print online list
	//-----------------------------------------
	
	function get_online_list()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$member_ids         = array();
		$to_load            = array();
		
		// Let's use the new config vars if they are available, else revert to the legacy variable names
		$_hide_whoschatting = ( isset($this->ipsclass->vars['chat_hide_whoschatting']) ) ? $this->ipsclass->vars['chat_hide_whoschatting'] : $this->ipsclass->vars['chat04_hide_whoschatting'];
		$_who_on            = ( isset($this->ipsclass->vars['chat_who_on']) ) ? $this->ipsclass->vars['chat_who_on'] : $this->ipsclass->vars['chat04_who_on'];
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! $_who_on )
		{
			return;
		}
		
		//-----------------------------------------
		// Sort and show :D
		//-----------------------------------------
		
		if ( is_array( $this->ipsclass->cache['chatting'] ) AND count( $this->ipsclass->cache['chatting'] ) )
		{
			foreach( $this->ipsclass->cache['chatting'] as $id => $data )
			{
				if ( $data['updated'] < ( time() - 120 ) )
				{
					continue;
				}
				
				$to_load[ $id ] = $id;
			}
		}
		
		//-----------------------------------------
		// Is this a root admin in disguise?
		// Is that kinda like a diamond in the rough?
		//-----------------------------------------
					
		$our_mgroups = array();
		
		if( $this->ipsclass->member['mgroup_others'] )
		{
			$our_mgroups = explode( ",", $this->ipsclass->clean_perm_string( $this->ipsclass->member['mgroup_others'] ) );
		}
		
		$our_mgroups[] = $this->ipsclass->member['mgroup'];		
		
		//-----------------------------------------
		// Got owt?
		//-----------------------------------------
		
		if ( count($to_load) )
		{
			$this->ipsclass->DB->build_query( array( 'select' => 'm.id, m.members_display_name, m.mgroup',
												     'from'   => array( 'members' => 'm' ),
												     'where'  => "m.id IN(".implode(",",$to_load).")",
	 												 'add_join' => array( 0 => array( 'select' => 's.login_type',
																					  'from'   => array( 'sessions' => 's' ),
																					  'where'  => 's.member_id=m.id',
																					  'type'   => 'left' ) ),
													 'order'  => 'm.members_display_name' ) );
			$this->ipsclass->DB->exec_query();
			
			while ( $m = $this->ipsclass->DB->fetch_row() )
			{
				$m['members_display_name'] = $this->ipsclass->make_name_formatted( $m['members_display_name'], $m['mgroup'] );
								
				if( $m['login_type'] )
				{
					if ( (in_array( $this->ipsclass->vars['admin_group'], $our_mgroups )) and ($this->ipsclass->vars['disable_admin_anon'] != 1) )
					{
						$member_ids[] = "<a href=\"{$this->ipsclass->base_url}showuser={$m['id']}\">{$m['members_display_name']}</a>";
					}
				}
				else
				{
					$member_ids[] = "<a href=\"{$this->ipsclass->base_url}showuser={$m['id']}\">{$m['members_display_name']}</a>";
				}
			}
		}		
		
		//-----------------------------------------
		// Got owt?
		//-----------------------------------------
		
		if ( count( $member_ids ) )
		{
			$final = implode( ",\n", $member_ids );
			
			$this->html = $this->ipsclass->compiled_templates['skin_boards']->whoschatting_show( intval(count($member_ids)), $final );
		}
		else
		{
			if ( ! $_hide_whoschatting )
			{
				$this->html = $this->ipsclass->compiled_templates['skin_boards']->whoschatting_empty();
			}
		}
		
		return $this->html;
	}





}



?>