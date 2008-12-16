<?php
/*
+---------------------------------------------------------------------------
|   Invision Power Board
|   ========================================
|   by Matthew Mecham
|   (c) 2004 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
+---------------------------------------------------------------------------
|   INVISION POWER BOARD IS NOT FREE SOFTWARE!
|   http://www.invisionboard.com
+---------------------------------------------------------------------------
|
|   > LOG IN MODULE: INTERNAL (IPB AUTH)
|   > Script written by Matt Mecham
|   > Date started: 12:25 Fri. 4th February 2005 (AD)
|
+---------------------------------------------------------------------------
| NOTES:
| Core module functions
+---------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class login_core
{
	# Globals
	var $ipsclass;
	
	# Returns
	var $auth_errors = array();
	var $return_code = "";
	var $member      = array();
	
	# Input
	var $is_admin_auth = 0;
	
	var $account_unlock = 0;
	
	/*-------------------------------------------------------------------------*/
	// Authorize against local DB:
	// $username: Log in username
	// $password: Plain text password
	/*-------------------------------------------------------------------------*/
	
	function auth_local( $username, $password )
	{
		$password = md5( $password );
		
		//-----------------------------------------
		// NAME LOG IN
		//-----------------------------------------
			
		if ( $this->ipsclass->vars['ipbli_usertype'] == 'username' )
		{
			$this->member = $this->ipsclass->DB->build_and_exec_query( array( 'select'   => 'm.id, m.name, m.members_display_name, m.members_created_remote, m.email, m.mgroup, m.member_login_key, m.member_login_key_expire, m.ip_address, m.login_anonymous, m.failed_logins, m.failed_login_count, m.joined, m.mgroup_others, m.org_perm_id',
																			  'from'     => array( 'members' => 'm' ),
																			  'where'    => "m.members_l_username='". strtolower($username) ."'",
																			  'add_join' => array( 0 => array( 'select' => 'g.*',
																			 								   'from'   => array( 'groups' => 'g' ),
																			  								   'where'  => 'g.g_id=m.mgroup',
																											   'type'   => 'inner' ) )) );
		
			//-----------------------------------------
			// Got a username?
			//-----------------------------------------
			
			if ( ! $this->member['id'] )
			{
				$this->return_code = 'NO_USER';
				return;
			}
			
			if ( $this->ipsclass->vars['ipb_bruteforce_attempts'] > 0 )
			{
				$failed_attempts = explode( ",", $this->ipsclass->clean_perm_string( $this->member['failed_logins'] ) );
				$failed_count	 = 0;
				$total_failed	 = 0;
				$thisip_failed	 = 0;
				$non_expired_att = array();
				
				if( is_array($failed_attempts) AND count($failed_attempts) )
				{
					foreach( $failed_attempts as $entry )
					{
						if ( ! strpos( $entry, "-" ) )
						{
							continue;
						}
						
						list ( $timestamp, $ipaddress ) = explode( "-", $entry );
						
						if ( ! $timestamp )
						{
							continue;
						}
						
						$total_failed++;
						
						if ( $ipaddress != $this->ipsclass->ip_address )
						{
							continue;
						}
						
						$thisip_failed++;
						
						if ( $this->ipsclass->vars['ipb_bruteforce_period'] AND
							$timestamp < time() - ($this->ipsclass->vars['ipb_bruteforce_period']*60) )
						{
							continue;
						}
						
						$non_expired_att[] = $entry;
						$failed_count++;
					}
					
					sort($non_expired_att);
					$oldest_entry  = array_shift( $non_expired_att );
					list($oldest,) = explode( "-", $oldest_entry );
				}

				if( $thisip_failed >= $this->ipsclass->vars['ipb_bruteforce_attempts'] )
				{
					if( $this->ipsclass->vars['ipb_bruteforce_unlock'] )
					{
						if( $failed_count >= $this->ipsclass->vars['ipb_bruteforce_attempts'] )
						{
							$this->account_unlock = $oldest;
					
							$this->return_code = 'ACCOUNT_LOCKED';
						}
					}
					else
					{
						$this->return_code = 'ACCOUNT_LOCKED';
					}
				}
			}
			
			$this->ipsclass->converge->converge_load_member( $this->member['email'] );
			
			if ( ! $this->ipsclass->converge->member['converge_id'] )
			{
				$this->return_code = 'WRONG_AUTH';
				return;
			}
		}
		
		//-----------------------------------------
		// EMAIL LOG IN
		//-----------------------------------------
		
		else
		{
			$email = $username;
			
			$this->ipsclass->converge->converge_load_member( $email );
			
			if ( $this->ipsclass->converge->member['converge_id'] )
			{
				$this->ipsclass->DB->build_query( array(
														  'select'   => 'm.*',
														  'from'     => array( 'members' => 'm' ),
														  'where'    => "m.email='".strtolower($username)."'",
														  'add_join' => array( 0 => array( 'select' => 'g.*',
																						   'from'   => array( 'groups' => 'g' ),
																						   'where'  => 'g.g_id=m.mgroup',
																						   'type'   => 'inner'
																						 )
																			)
												 )     );

				$this->ipsclass->DB->exec_query();

				$this->member      = $this->ipsclass->DB->fetch_row();
				
				if( $this->ipsclass->vars['ipb_bruteforce_attempts'] > 0 )
				{
					$failed_attempts = explode( ",", $this->ipsclass->clean_perm_string( $this->member['failed_logins'] ) );
					$failed_count	 = 0;
					$total_failed	 = 0;
					$thisip_failed	 = 0;
					$non_expired_att = array();
					
					if( is_array($failed_attempts) AND count($failed_attempts) )
					{
						foreach( $failed_attempts as $entry )
						{
							if( !strpos( $entry, "-" ) )
							{
								continue;
							}

							list($timestamp,$ipaddress) = explode( "-", $entry );
							
							if( !$timestamp )
							{
								continue;
							}
							
							$total_failed++;
							
							if( $ipaddress != $this->ipsclass->ip_address )
							{
								continue;
							}
							
							$thisip_failed++;
							
							if( $this->ipsclass->vars['ipb_bruteforce_period'] AND
								$timestamp < time() - ($this->ipsclass->vars['ipb_bruteforce_period']*60) )
							{
								continue;
							}
							
							$non_expired_att[] = $entry;
							$failed_count++;
						}
					
						sort($non_expired_att);
						$oldest_entry  = array_shift( $non_expired_att );
						list($oldest,) = explode( "-", $oldest_entry );
					}
	
					if( $thisip_failed >= $this->ipsclass->vars['ipb_bruteforce_attempts'] )
					{
						if( $this->ipsclass->vars['ipb_bruteforce_unlock'] )
						{
							if( $failed_count >= $this->ipsclass->vars['ipb_bruteforce_attempts'] )
							{
								$this->account_unlock = $oldest;
						
								$this->return_code = 'ACCOUNT_LOCKED';
							}
						}
						else
						{
							$this->return_code = 'ACCOUNT_LOCKED';
						}
					}
				}
			}
			else
			{
				$this->return_code = 'NO_USER';
				return;
			}
		}
		
		//-----------------------------------------
		// Check password...
		//-----------------------------------------
		
		if ( $this->ipsclass->converge->converge_authenticate_member( $password ) != TRUE )
		{ 
			if( $this->ipsclass->vars['ipb_bruteforce_attempts'] > 0 )
			{
				$failed_logins 	 = explode( ",", $this->member['failed_logins'] );
				$failed_logins[] = time().'-'.$this->ipsclass->ip_address;
				
				$failed_count	 = 0;
				$total_failed	 = 0;
				$non_expired_att = array();
				
				foreach( $failed_logins as $entry )
				{
					list($timestamp,$ipaddress) = explode( "-", $entry );
					
					if( !$timestamp )
					{
						continue;
					}
					
					$total_failed++;
					
					if( $ipaddress != $this->ipsclass->ip_address )
					{
						continue;
					}
					
					if( $this->ipsclass->vars['ipb_bruteforce_period'] > 0
						AND $timestamp < time() - ($this->ipsclass->vars['ipb_bruteforce_period']*60) )
					{
						continue;
					}
					
					$failed_count++;
					$non_expired_att[] = $entry;
				}
				
				if( $this->member['id'] )
				{
					$this->ipsclass->DB->do_update( "members", array( 'failed_logins' => implode( ",", $failed_logins ),
																  'failed_login_count' => $total_failed ), "id=".$this->member['id'] );
				}

				if( $failed_count >= $this->ipsclass->vars['ipb_bruteforce_attempts'] )
				{
					if( $this->ipsclass->vars['ipb_bruteforce_unlock'] )
					{
						sort($non_expired_att);
						$oldest_entry  = array_shift( $non_expired_att );
						list($oldest,) = explode( "-", $oldest_entry );
						
						$this->account_unlock = $oldest;
					}
											
					$this->return_code = 'ACCOUNT_LOCKED';
					return;
				}
			}
			
			$this->return_code = 'WRONG_AUTH';
			return;
		}
		else if( $this->return_code == 'ACCOUNT_LOCKED' )
		{
			return;
		}
		else
		{
			$this->return_code = 'SUCCESS';
			return;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Create local member:
	// $username: Log in username
	// $password: Plain text password
	/*-------------------------------------------------------------------------*/
	
	/**
	* Creates local member
	* @param	string	Username
	* @param	string	MD5 once password
	* @param	string	Email Address (optional)
	* @return	int		Member ID
	*/
	function _create_local_member( $username, $md5_password, $email_address='', $joined='', $ip_address='' )
	{
		//-----------------------------------------
		// Custom profile field stuff
		//-----------------------------------------
		
		require_once( ROOT_PATH.'sources/classes/class_custom_fields.php' );
    	$fields              = new custom_fields( $this->ipsclass->DB );
    	$fields->cache_data  = $this->ipsclass->cache['profilefields'];
    	
    	$fields->init_data();
    	$fields->parse_to_save( 1 );
    	
		//-----------------------------------------
		// Populate member table(s)
		//-----------------------------------------
		
		$mem_group = $this->ipsclass->vars['subsm_enforce'] ? $this->ipsclass->vars['subsm_nopkg_group'] : $this->ipsclass->vars['member_group'];
		$timenow   = $joined ? $joined : time();
		$email_tmp = $email_address ? $email_address : $username.'@'.$timenow;
			
		//-----------------------------------------
		// Are we asking the member or admin to preview?
		//-----------------------------------------
		
		$member = array(
						 'name'                   => $username,
						 'members_l_username'	  => strtolower($username),
						 'members_created_remote' => 1,
						 'email'                  => $email_tmp,
						 'member_login_key'       => $this->ipsclass->converge->generate_auto_log_in_key(),
						 'mgroup'                 => $mem_group,
						 'posts'                  => 0,
						 'joined'                 => $timenow,
						 'ip_address'             => $ip_address ? $ip_address : $this->ipsclass->ip_address,
						 'view_sigs'              => 1,
						 'email_pm'               => 1,
						 'view_img'               => 1,
						 'view_avs'               => 1,
						 'restrict_post'          => 0,
						 'view_pop'               => 1,
						 'msg_total'              => 0,
						 'new_msg'                => 0,
						 'coppa_user'             => 0,
						 'language'               => $this->ipsclass->vars['default_language'],
						 'subs_pkg_chosen'        => 0
					   );
		
		//-----------------------------------------
		// Insert: CONVERGE
		//-----------------------------------------
		
		$salt     = $this->ipsclass->converge->generate_password_salt(5);
		$passhash = $this->ipsclass->converge->generate_compiled_passhash( $salt, $md5_password );
					   
		$converge = array( 'converge_email'     => $email_tmp,
						   'converge_joined'    => $timenow,
						   'converge_pass_hash' => $passhash,
						   'converge_pass_salt' => $salt
						 );
					   
		$this->ipsclass->DB->do_insert( 'members_converge', $converge );
		
		//-----------------------------------------
		// Get converges auto_increment user_id
		//-----------------------------------------
		
		$member_id    = $this->ipsclass->DB->get_insert_id();
		$member['id'] = $member_id;
		
		//-----------------------------------------
		// Insert: MEMBERS
		//-----------------------------------------
		
		$this->ipsclass->DB->force_data_type = array( 'name'                 => 'string',
													  'members_display_name' => 'string' );
													
		$this->ipsclass->DB->do_insert( 'members', $member );
		
		//-----------------------------------------
		// Insert: MEMBER EXTRA
		//-----------------------------------------
		
		$this->ipsclass->DB->do_insert( 'member_extra', array( 'id' => $member_id, 'vdirs' => 'in:Inbox|sent:Sent Items' ) );
		
		//-----------------------------------------
		// Insert into the custom profile fields DB
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'pfields_content', 'where' => 'member_id='.$member['id'] ) );
		
		$fields->out_fields['member_id'] = $member['id'];
				
		$this->ipsclass->DB->do_insert( 'pfields_content', $fields->out_fields );
		
		//-----------------------------------------
		// Insert into partial ID table
		//-----------------------------------------
		
		$this->ipsclass->DB->do_insert( 'members_partial', array( 'partial_member_id' => $member['id'],
																  'partial_date'      => $timenow,
																  'partial_email_ok'  => $email_address ? 1 : 0 ) );
																
		return array_merge( $member, array( 'timenow' => $timenow ) );
	}
	
	/*-------------------------------------------------------------------------*/
	// Create local member:
	// $username: Log in username
	// $password: Plain text password
	/*-------------------------------------------------------------------------*/
	
	function create_local_member( $username, $password, $email_address='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$username      = trim( $username );
		$password      = trim( $password );
		$md_5_password = md5( $password );
		
		//-----------------------------------------
		// Create
		//-----------------------------------------
		
		$member = $this->_create_local_member( $username, $md_5_password, $email_address );
		
		//-----------------------------------------
		// Now bounce onto "welcome page"
		// where we'll ask for details
		//-----------------------------------------
		
		if( $this->is_admin_auth )
		{
			$this->ipsclass->admin->redirect( $this->ipsclass->vars['board_url'].'/index.php?act=reg&CODE=complete_login&mid='.$member['id'].'&key='.$member['timenow'], $this->ipsclass->lang['partial_login'] );
		}
		else
		{	
			$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['partial_login'], 'act=reg&CODE=complete_login&mid='.$member['id'].'&key='.$member['timenow'] );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Admin: If set to passthru, check local first
	// $username: Log in username
	// $password: Plain text password
	/*-------------------------------------------------------------------------*/
	
	function admin_auth_local( $username, $password )
	{
		$password = md5( $password );
		
		//-----------------------------------------
		// NAME LOG IN
		//-----------------------------------------
			
		if ( $this->ipsclass->vars['ipbli_usertype'] == 'username' )
		{
			$this->member = $this->ipsclass->DB->build_and_exec_query( array( 'select'   => 'm.*',
																			  'from'     => array( 'members' => 'm' ),
																			  'where'    => "m.members_l_username='". $username ."' and m.members_created_remote=0",
																			  'add_join' => array( 0 => array( 'select' => 'g.*',
																			 								   'from'   => array( 'groups' => 'g' ),
																			  								   'where'  => 'g.g_id=m.mgroup',
																											   'type'   => 'inner' ) )) );
		
			//-----------------------------------------
			// Got a username?
			//-----------------------------------------
			
			if ( ! $this->member['id'] )
			{
				$this->return_code = 'NO_USER';
				return;
			}
			
			if ( $this->ipsclass->vars['ipb_bruteforce_attempts'] > 0 )
			{
				$failed_attempts = explode( ",", $this->ipsclass->clean_perm_string( $this->member['failed_logins'] ) );
				$failed_count	 = 0;
				$total_failed	 = 0;
				$thisip_failed	 = 0;
				$non_expired_att = array();
				
				if( is_array($failed_attempts) AND count($failed_attempts) )
				{
					foreach( $failed_attempts as $entry )
					{
						if ( ! strpos( $entry, "-" ) )
						{
							continue;
						}
						
						list ( $timestamp, $ipaddress ) = explode( "-", $entry );
						
						if ( ! $timestamp )
						{
							continue;
						}
						
						$total_failed++;
						
						if ( $ipaddress != $this->ipsclass->ip_address )
						{
							continue;
						}
						
						$thisip_failed++;
						
						if ( $this->ipsclass->vars['ipb_bruteforce_period'] AND
							$timestamp < time() - ($this->ipsclass->vars['ipb_bruteforce_period']*60) )
						{
							continue;
						}
						
						$failed_count++;
						$non_expired_att[] = $entry;
					}
					
					sort($non_expired_att);
					$oldest_entry  = array_shift( $non_expired_att );
					list($oldest,) = explode( "-", $oldest_entry );
				}

				if( $thisip_failed >= $this->ipsclass->vars['ipb_bruteforce_attempts'] )
				{
					if( $this->ipsclass->vars['ipb_bruteforce_unlock'] )
					{
						if( $failed_count >= $this->ipsclass->vars['ipb_bruteforce_attempts'] )
						{
							$this->account_unlock = $oldest;
					
							$this->return_code = 'ACCOUNT_LOCKED';
						}
					}
					else
					{
						$this->return_code = 'ACCOUNT_LOCKED';
					}
				}
			}
			
			$this->ipsclass->converge->converge_load_member( $this->member['email'] );
			
			if ( ! $this->ipsclass->converge->member['converge_id'] )
			{
				$this->return_code = 'WRONG_AUTH';
				return;
			}
		}
		
		//-----------------------------------------
		// EMAIL LOG IN
		//-----------------------------------------
		
		else
		{
			$email = $username;
			
			$this->ipsclass->converge->converge_load_member( $email );
			
			if ( $this->ipsclass->converge->member['converge_id'] )
			{
				$this->ipsclass->DB->build_query( array(
														  'select'   => 'm.*',
														  'from'     => array( 'members' => 'm' ),
														  'where'    => "m.email='".strtolower($username)."' AND m.members_created_remote=0",
														  'add_join' => array( 0 => array( 'select' => 'g.*',
																						   'from'   => array( 'groups' => 'g' ),
																						   'where'  => 'g.g_id=m.mgroup',
																						   'type'   => 'inner'
																						 )
																			)
												 )     );

				$this->ipsclass->DB->exec_query();

				$this->member      = $this->ipsclass->DB->fetch_row();
				
				if( $this->ipsclass->vars['ipb_bruteforce_attempts'] > 0 )
				{
					$failed_attempts = explode( ",", $this->ipsclass->clean_perm_string( $this->member['failed_logins'] ) );
					$failed_count	 = 0;
					$total_failed	 = 0;
					$thisip_failed	 = 0;
					
					if( is_array($failed_attempts) AND count($failed_attempts) )
					{
						foreach( $failed_attempts as $entry )
						{
							if( !strpos( $entry, "-" ) )
							{
								continue;
							}

							list($timestamp,$ipaddress) = explode( "-", $entry );
							
							if( !$timestamp )
							{
								continue;
							}
							
							$total_failed++;
							
							if( $ipaddress != $this->ipsclass->ip_address )
							{
								continue;
							}
							
							$thisip_failed++;
							
							if( $this->ipsclass->vars['ipb_bruteforce_period'] AND
								$timestamp < time() - ($this->ipsclass->vars['ipb_bruteforce_period']*60) )
							{
								continue;
							}
							
							$failed_count++;
						}
						
						sort($failed_attempts);
						$oldest_entry  = array_shift( $failed_attempts );
						list($oldest,) = explode( "-", $oldest_entry );
					}
	
					if( $thisip_failed >= $this->ipsclass->vars['ipb_bruteforce_attempts'] )
					{
						if( $this->ipsclass->vars['ipb_bruteforce_unlock'] )
						{
							if( $failed_count >= $this->ipsclass->vars['ipb_bruteforce_attempts'] )
							{
								$this->account_unlock = $oldest;
						
								$this->return_code = 'ACCOUNT_LOCKED';
							}
						}
						else
						{
							$this->return_code = 'ACCOUNT_LOCKED';
						}
					}
				}
			}
			else
			{
				$this->return_code = 'NO_USER';
				return;
			}
		}
		
		//-----------------------------------------
		// Check password...
		//-----------------------------------------
		
		if ( $this->ipsclass->converge->converge_authenticate_member( $password ) != TRUE )
		{ 
			if( $this->ipsclass->vars['ipb_bruteforce_attempts'] > 0 )
			{
				$failed_logins 	 = explode( ",", $this->member['failed_logins'] );
				$failed_logins[] = time().'-'.$this->ipsclass->ip_address;
				
				$failed_count	 = 0;
				$total_failed	 = 0;
				
				foreach( $failed_logins as $entry )
				{
					list($timestamp,$ipaddress) = explode( "-", $entry );
					
					if( !$timestamp )
					{
						continue;
					}
					
					$total_failed++;
					
					if( $ipaddress != $this->ipsclass->ip_address )
					{
						continue;
					}
					
					if( $this->ipsclass->vars['ipb_bruteforce_period'] > 0
						AND $timestamp < time() - ($this->ipsclass->vars['ipb_bruteforce_period']*60) )
					{
						continue;
					}
					
					$failed_count++;
				}
				
				if( $this->member['id'] )
				{
					$this->ipsclass->DB->do_update( "members", array( 'failed_logins' => implode( ",", $failed_logins ),
																  'failed_login_count' => $total_failed ), "id=".$this->member['id'] );
				}

				if( $failed_count >= $this->ipsclass->vars['ipb_bruteforce_attempts'] )
				{
					if( $this->ipsclass->vars['ipb_bruteforce_unlock'] )
					{
						sort($failed_attempts);
						$oldest_entry  = array_shift( $failed_attempts );
						list($oldest,) = explode( "-", $oldest_entry );
						
						$this->account_unlock = $oldest;
					}
											
					$this->return_code = 'ACCOUNT_LOCKED';
					return;
				}
			}
			
			$this->return_code = 'WRONG_AUTH';
			return;
		}
		else if( $this->return_code == 'ACCOUNT_LOCKED' )
		{
			return;
		}
		else
		{
			$this->return_code = 'SUCCESS';
			return;
		}
	}	
}

?>