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
|   > PORTAL PLUG IN MODULE: SHOW ONLINE USERS
|   > Module written by Matt Mecham
|   > Date started: Tuesday 2nd August 2005 (12:56)
+--------------------------------------------------------------------------
*/

/**
* Portal Plug In Module
*
* This module shows the online users
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
* This module shows the online users
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

class ppi_online_users
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
	function online_users_show()
	{
 		$this->sep_char = '<{ACTIVE_LIST_SEP}>';
 		
		//-----------------------------------------
		// Get the users from the DB
		//-----------------------------------------
		
		$cut_off = ($this->ipsclass->vars['au_cutoff'] ? $this->ipsclass->vars['au_cutoff'] : 15) * 60;
		$time    = time() - $cut_off;
		$qe      = "";
		$rows    = array( 0 => array( 'login_type'   => substr($this->ipsclass->member['login_anonymous'],0, 1),
									  'running_time' => time(),
									  'member_id'    => $this->ipsclass->member['id'],
									  'member_name'  => $this->ipsclass->member['members_display_name'],
									  'member_group' => $this->ipsclass->member['mgroup'] ) );
		
		if ( $this->ipsclass->member['id'] )
		{
			$qe = "member_id !=".intval($this->ipsclass->member['id'])." AND ";
		}
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'id, member_id, member_name, login_type, running_time, member_group',
													  'from'   => 'sessions',
													  'where'  => $qe." running_time > $time",
													  'order'  => "running_time DESC"
											 )      );
		
		
		$this->ipsclass->DB->simple_exec();
		
		//-----------------------------------------
		// FETCH...
		//-----------------------------------------
		
		while ($r = $this->ipsclass->DB->fetch_row() )
		{
			$rows[] = $r;
		}
		
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
						if ( $this->ipsclass->member['mgroup'] == $this->ipsclass->vars['admin_group'] )
						{
							$active['NAMES'] .= "{$result['member_name']}*{$this->sep_char} \n";
						}
					}
					else
					{
						$active['NAMES'] .= "{$result['member_name']}{$this->sep_char} \n";
					}
					
					$cached[ $result['member_name'] ] = 1;
				}
				else
				{
					//-----------------------------------------
					// Yup, count others as guest
					//-----------------------------------------
					
					$active['GUESTS']++;
				}
			}
			
			//-----------------------------------------
			// Guest?
			//-----------------------------------------
			
			else if ($result['member_id'] == 0 )
			{
				$active['GUESTS']++;
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
						if ( ($this->ipsclass->member['mgroup'] == $this->ipsclass->vars['admin_group']) and ($this->ipsclass->vars['disable_admin_anon'] != 1) )
						{
							$active['NAMES'] .= "<a href='{$this->ipsclass->base_url}showuser={$result['member_id']}' title='{$last_date}'>{$result['member_name']}</a>*{$this->sep_char} \n";
							$active['ANON']++;
						}
						else
						{
							$active['ANON']++;
						}
					}
					else
					{
						$active['MEMBERS']++;
						$active['NAMES'] .= "<a href='{$this->ipsclass->base_url}showuser={$result['member_id']}' title='{$last_date}'>{$result['member_name']}</a>{$this->sep_char} \n";
					}
				}
			}
		}
		
		$active['names'] = preg_replace( "/".preg_quote($this->sep_char)."$/", "", trim($active['NAMES']) );
		
		$active['total']    = $active['MEMBERS'] + $active['GUESTS'] + $active['ANON'];
		$active['visitors'] = $active['GUESTS']  + $active['ANON'];
		$active['members']  = $active['MEMBERS'];
		
		//-----------------------------------------
		// Parse language
		//-----------------------------------------
		
		$breakdown = sprintf( $this->ipsclass->lang['online_breakdown'], intval($active['total']) );
		$split     = sprintf( $this->ipsclass->lang['online_split']    , intval($active['members']), intval($active['visitors']) );
		
 		
 		return $this->ipsclass->compiled_templates['skin_portal']->tmpl_onlineusers($breakdown, $split, $active['names']);
 	}

}

?>