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
|   > $Date: 2007-01-04 09:49:22 -0500 (Thu, 04 Jan 2007) $
|   > $Revision: 819 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Show online users
|   > Module written by Matt Mecham
|   > Date started: 12th March 2002
|
|	> Module Version Number: 1.0.0
|   > DBA Checked: Thu 20 May 2004
|   > Quality Checked: Wed 15 Sept. 2004
+--------------------------------------------------------------------------
*/


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class online
{

    var $output     = "";
    var $page_title = "";
    var $nav        = array();
    var $html       = "";
    var $first      = 0;
    var $perpage    = 25;
    
    var $forums     = array();
    var $cats       = array();
    var $sessions   = array();
    var $where      = array();
    
    var $seen_name  = array();
    
    /*-------------------------------------------------------------------------*/
	// AUTO RUN
	/*-------------------------------------------------------------------------*/

    function auto_run()
    {
		//-----------------------------------------
    	// Are we allowed to see the online list?
    	//-----------------------------------------
    	
    	$this->ipsclass->input['st'] = intval($this->ipsclass->input['st']) >=0 ? intval($this->ipsclass->input['st']) : 0;
    	
    	if ( $this->ipsclass->vars['allow_online_list'] != 1 )
    	{
    		$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission') );
    	}
    	
    	//-----------------------------------------
    	// Require the HTML and language modules
    	//-----------------------------------------
    	
		$this->ipsclass->load_language('lang_online');
    	$this->ipsclass->load_template('skin_online');
    	
    	$this->base_url        = $this->ipsclass->base_url;
    	
    	//-----------------------------------------
    	// Build up our language hash
    	//-----------------------------------------
    	
    	foreach ($this->ipsclass->lang as $k => $v)
    	{
    		if ( preg_match( "/^WHERE_(\w+)$/", $k, $match ) )
    		{
    			$this->where[ $match[1] ] = $this->ipsclass->lang[$k];
    		}
    	}
    	
    	unset($match);
    	
    	//-----------------------------------------
    	// What to do?
    	//-----------------------------------------
    	
    	switch($this->ipsclass->input['CODE'])
    	{
    		case 'listall':
    			$this->list_all();
    			break;
    		default:
    			$this->list_all();
    			break;
    	}
    	
    	//-----------------------------------------
    	// If we have any HTML to print, do so...
    	//-----------------------------------------
    	
    	$this->ipsclass->print->add_output("$this->output");
        $this->ipsclass->print->do_output( array( 'TITLE' => $this->page_title, 'JS' => 0, 'NAV' => $this->nav ) );
 	}
 	
 	
	/*-------------------------------------------------------------------------*/
	// list_all
	// ------------------
	// List all online users
	/*-------------------------------------------------------------------------*/
	
	function list_all()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->first      = intval($this->ipsclass->input['st']) > 0 ? intval($this->ipsclass->input['st']) : 0;
		$show_mem_html    = "";
		$sort_order_html  = "";
		$sort_key_html    = "";
		
		$final            = array();
		$tid_array        = array();
		$topics           = array();
		$modules          = array();
		$final_modules    = array();
		
		$show_mem         = array( 'reg', 'guest', 'all' );
		$sort_order       = array( 'desc', 'asc' );
		$sort_key         = array( 'click', 'name' );
		
		$show_mem_value   = (isset($this->ipsclass->input['show_mem']) AND $this->ipsclass->input['show_mem']) ? $this->ipsclass->input['show_mem']   : 'all';
		$sort_order_value = (isset($this->ipsclass->input['sort_order']) AND $this->ipsclass->input['sort_order']) ? $this->ipsclass->input['sort_order'] : 'desc';
		$sort_key_value   = (isset($this->ipsclass->input['sort_key']) AND $this->ipsclass->input['sort_key']) ? $this->ipsclass->input['sort_key']   : 'click';
		
		//-----------------------------------------
		// Get member groups
		//-----------------------------------------
				
		$our_mgroups = array();
		
		if( $this->ipsclass->member['mgroup_others'] )
		{
			$our_mgroups = explode( ",", $this->ipsclass->clean_perm_string( $this->ipsclass->member['mgroup_others'] ) );
		}
		
		$our_mgroups[] = $this->ipsclass->member['mgroup'];
				
		//-----------------------------------------
		// Build lists
		//-----------------------------------------
		
		$oo = "<option ";
		$oc = "</option>\n";
		
		foreach( $show_mem as $k )
		{
			$s = "";
			
			if ( $show_mem_value == $k )
			{
				$s = ' selected="selected" ';
			}
			
			$show_mem_html .= $oo.'value="'.$k.'"'.$s.'>'.$this->ipsclass->lang['s_show_mem_'.$k].$oc;
		}
		
		foreach( $sort_order as $k )
		{
			$s = "";
			
			if ( $sort_order_value == $k )
			{
				$s = ' selected="selected" ';
			}
			
			$sort_order_html .= $oo.'value="'.$k.'"'.$s.'>'.$this->ipsclass->lang['s_sort_order_'.$k].$oc;
		}
		
		foreach( $sort_key as $k )
		{
			$s = "";
			
			if ( $sort_key_value == $k )
			{
				$s = ' selected="selected" ';
			}
			
			$sort_key_html .= $oo.'value="'.$k.'"'.$s.'>'.$this->ipsclass->lang['s_sort_key_'.$k].$oc;
		}
		
		if ($this->ipsclass->vars['au_cutoff'] == "")
		{
			$this->ipsclass->vars['au_cutoff'] = 15;
		}
			
		$cut_off  = $this->ipsclass->vars['au_cutoff'] * 60;
		$t_time   = time() - $cut_off;
		
		$db_order = $sort_order_value == 'asc' ? 'asc' : 'desc';
		$db_key   = $sort_key_value   == 'click' ? 'running_time' : 'member_name';

		switch ($show_mem_value)
		{
			case 'reg':
				$db_mem = " AND s.member_id <> 0 AND s.member_group <> {$this->ipsclass->vars['guest_group']}";
				break;
			case 'guest':
				$db_mem = " AND s.member_group = {$this->ipsclass->vars['guest_group']} ";
				break;
			default:
				$db_mem = "";
				break;
		}
		
		$remove_spiders = 0;
		
		if ( $this->ipsclass->vars['spider_anon'] )
		{
			if ( !in_array( $this->ipsclass->vars['admin_group'], $our_mgroups ) )
			{
				$remove_spiders = 1;
			}
		}

		$remove_anon = 1;
		
		if ( (in_array( $this->ipsclass->vars['admin_group'], $our_mgroups )) and ($this->ipsclass->vars['disable_admin_anon'] != 1) )
		{
			$remove_anon = 0;
		}
		
		$this->ipsclass->DB->cache_add_query( 'session_get_count', array( 'time' => $t_time, 'db_mem' => $db_mem, 'remove_anon' => $remove_anon, 'remove_spiders' => $remove_spiders ) );
		$this->ipsclass->DB->cache_exec_query();
		
		$max = $this->ipsclass->DB->fetch_row();
		
		$links = $this->ipsclass->build_pagelinks(  array( 'TOTAL_POSS'  => $max['total_sessions'],
														   'PER_PAGE'    => 25,
														   'CUR_ST_VAL'  => $this->first,
														   'L_SINGLE'     => "",
														   'L_MULTI'      => $this->ipsclass->lang['pages'],
														   'BASE_URL'     => $this->base_url."act=Online&amp;CODE=listall&amp;sort_key=$sort_key_value&amp;sort_order=$sort_order_value&amp;show_mem=$show_mem_value"
														 )
												  );
									   
		$this->output = $this->ipsclass->compiled_templates['skin_online']->Page_header($links);
		
		//-----------------------------------------
		// Grab all the current sessions.
		//-----------------------------------------
		
		$this->ipsclass->DB->cache_add_query( 'session_get_sessions', array( 'time' => $t_time, 'db_mem' => $db_mem, 'remove_anon' => $remove_anon, 'remove_spiders' => $remove_spiders,
													'db_key' => $db_key, 'db_order' => $db_order, 'start' => $this->first, 'finish' => 25 ) );
		$outer = $this->ipsclass->DB->cache_exec_query();
		
		if( !$this->ipsclass->DB->get_num_rows($outer) && $this->first > 0 )
		{
			// We are request page 2 - but there is no page 2 now...
			$this->ipsclass->boink_it( $this->ipsclass->base_url."act=Online&amp;sortkey={$sort_key_value}&amp;show_mem={$show_mem_value}&amp;sort_order={$sort_order_value}" );
		}
		
		while( $r = $this->ipsclass->DB->fetch_row($outer) )
		{
			$final[ $r['id'] ] = $r;
			
			//-----------------------------------------
			// Module?
			//-----------------------------------------
			
			if ( strstr( $r['location'], 'mod:' ) )
			{
				$modules[ str_replace( 'mod:', '', $r['location'] ) ] [ $r['id'] ] = $r;
			}
			
			//-----------------------------------------
			// IN topic?
			//-----------------------------------------
			
			if ( $r['location_1_id'] AND $r['location_1_type'] == 'topic' )
			{
				$tid_array[ $r['location_1_id'] ] = $r['location_1_id'];
			}
		}
		
		//-----------------------------------------
		// Post process modules
		//-----------------------------------------
		
		if ( count( $modules ) )
		{
			foreach( $modules as $module_name => $module_array )
			{
				$filename = ROOT_PATH.'sources/components_location/'.$this->ipsclass->txt_alphanumerical_clean( $module_name ).'.php';
				
				if ( file_exists( $filename ) )
				{
					require_once( $filename );
					$toload           = 'components_location_'.$module_name;
					$loader           = new $toload;
					$loader->ipsclass =& $this->ipsclass;
					
					$tmp = $loader->parse_online_entries( $module_array );
					
					if ( is_array( $tmp ) and count( $tmp ) )
					{
						foreach( $tmp as $ssid => $arr )
						{
							$final[ $ssid ] = $arr;
						}
					}
				}
			}
		}
		
		//-----------------------------------------
		// Do topics
		//-----------------------------------------
		
		if ( count($tid_array) > 0 )
		{
			$tid_string = implode( ",", $tid_array );
			
			$this->ipsclass->DB->simple_construct( array( 'select' => 'tid, title', 'from' => 'topics', 'where' => "tid IN ($tid_string)" ) );
			$this->ipsclass->DB->simple_exec();
		
			while ( $t = $this->ipsclass->DB->fetch_row() )
			{
				$topics[ $t['tid'] ] = $t['title'];
			}
		}
		
		//-----------------------------------------
		// LOOPY
		//-----------------------------------------
		
		foreach( $final as $sess )
		{
			//-----------------------------------------
			// Is this a member, and have we seen them before?
			// Proxy servers, etc can confuse the session handler,
			// creating duplicate session IDs for the same user when
			// their IP address changes.
			//-----------------------------------------
			
			$inv = '';
			
			if ( strstr( $sess['id'], '_session' ) )
			{
				$sess['is_bot'] = 1;
				
				if ( $this->ipsclass->vars['spider_anon'] )
				{
					if ( in_array( $this->ipsclass->vars['admin_group'], $our_mgroups ) )
					{
						$inv = '*';
					}
					else
					{
						$sess['member_id']   = '';
						$sess['member_name'] = '';
						$sess['in_error']    = 1;
					}
				}
			}
			else if ($sess['login_type'] == 1)
			{
				if ( (in_array( $this->ipsclass->vars['admin_group'], $our_mgroups )) and ($this->ipsclass->vars['disable_admin_anon'] != 1) )
				{
					$inv = '*';
				}
				else
				{
					$sess['member_id']   = '';
					$sess['member_name'] = '';
					$sess['in_error']    = 1;
					$sess['prefix']      = "";
					$sess['suffix']      = ""; 
				}
			}
			
			//-----------------------------------------
			// ICheck for dupes
			//-----------------------------------------
			
			if ( ! empty($sess['member_name']) )
			{
				if (isset($this->seen_name[ $sess['member_name'] ]) )
				{
					continue;
				}
				else
				{
					$this->seen_name[ $sess['member_name'] ] = 1;
				}
			}
			
			if ( !$sess['member_id'] AND (!isset($sess['is_bot']) OR !$sess['is_bot']) )
			{
				$sess['member_name']  = $this->ipsclass->lang['guest'];
			}
			
			$sess['member_name'] = $this->ipsclass->make_name_formatted( $sess['member_name'], $sess['member_group'] );
			
			//-----------------------------------------
			// Figure out location
			//-----------------------------------------
			
			if ( $sess['in_error'] )
			{
				$line = " {$this->ipsclass->lang['board_index']}";
			}
			else if ( $sess['location'] OR $sess['_parsed'] )
			{
				$line = "";
				
				//-----------------------------------------
				// PARSED?
				//-----------------------------------------
				
				if ( isset($sess['_parsed']) AND $sess['_parsed'] )
				{
					$line = "<a href='{$sess['_url']}'>{$sess['_text']}</a>";
				}
				
				//-----------------------------------------
				// NOT PARSED
				//-----------------------------------------
				
				else
				{
					list($act, $pid) = explode( ",", $sess['location'] );
					
					$fid = ($sess['location_2_type'] == 'forum' AND $sess['location_2_id']) ? $sess['location_2_id'] : 0;
					$tid = ($sess['location_1_type'] == 'topic' AND $sess['location_1_id']) ? $sess['location_1_id'] : 0;
					$act = strtolower($act);
					
					//-----------------------------------------
					// Really in a forum?
					//-----------------------------------------
					
					if ( $act == 'sf' && ! $fid )
					{
						$act = '';
					}
					
					if ( $act == 'st' && ! $tid )
					{
						$act = '';
					}
					
					if ( $act == 'post' && ! $fid )
					{
						$act = '';
					}
					
					if ( isset($act) )
					{
						if( $act == 'post' AND $tid )
						{
							$line = $this->where[ 'postrep' ];
						}
						else if( $act == 'post' AND $fid )
						{
							$line = $this->where[ 'postnew' ];
						}
						else
						{
							if( in_array( $act, array( 'members', 'help', 'calendar', 'online', 'boardrules' ) ) )
							{
								$line = isset($this->where[ $act ]) ? "<a href='{$this->ipsclass->base_url}act={$act}'>{$this->where[ $act ]}</a>" : $this->ipsclass->lang['board_index'];
							}
							else
							{
								$line = isset($this->where[ $act ]) ? $this->where[ $act ] : $this->ipsclass->lang['board_index'];
							}
						}
					}
					
					if ( $fid != "" and ($act == 'sf' or $act == 'st' or $act == 'post'))
					{
						$deny = 1;
						
						$deny = $this->ipsclass->forums->forums_quick_check_access( $fid );
						
						if ($deny != 1)
						{
							if ( ($tid > 0) )
							{
								$line .= " <a href='{$this->base_url}showtopic=$tid'>{$topics[$tid]}</a>";
							}
							else
							{
								$line .= " <a href='{$this->base_url}showforum=$fid'>{$this->ipsclass->forums->forum_by_id[ $fid ]['name']}</a>";
							}
						}
						else
						{
							$line = " {$this->ipsclass->lang['board_index']}";
						}
					}
				}
			}
			else
			{
				$line = " {$this->ipsclass->lang['board_index']}";
			}
			
			$sess['where_line'] = $line;
			
			if ( (in_array( $this->ipsclass->vars['admin_group'], $our_mgroups )) and ($this->ipsclass->vars['disable_online_ip'] != 1) )
			{
				$sess['ip_address'] = " ( ".$sess['ip_address']." )";
			}
			else
			{
				$sess['ip_address'] = "";
			}
			
			if ( $sess['member_id'] )
			{
				$sess['member_name'] = "<a href='{$this->base_url}showuser={$sess['member_id']}'>{$sess['member_name']}</a>{$inv} {$sess['ip_address']}";
			}
			else if( isset($sess['is_bot']) AND $sess['is_bot'] )
			{
				$sess['member_name'] = $sess['member_name'].$inv;
			}
			
			$sess['running_time'] = $this->ipsclass->get_date( $sess['running_time'], 'LONG' );
			
			$this->output .= $this->do_html_row($sess);
			
		}
		
		$this->output .= $this->ipsclass->compiled_templates['skin_online']->Page_end($show_mem_html, $sort_order_html, $sort_key_html, $links);
		
		$this->page_title = $this->ipsclass->lang['page_title'];
		$this->nav        = array( $this->ipsclass->lang['page_title']);
	}
	
	/*-------------------------------------------------------------------------*/
	// Process row
	/*-------------------------------------------------------------------------*/
	
	function do_html_row($sess)
	{
		if ($sess['member_name'] and $sess['member_id'])
		{
			$sess['msg_icon'] = "<a href='{$this->base_url}act=Msg&amp;CODE=04&amp;MID={$sess['member_id']}'><{P_MSG}></a>";
		}
		else
		{
			if ( !isset($sess['is_bot']) OR !$sess['is_bot'] )
			{
				$sess['member_name']  .= ' '.$sess['ip_address'];
				$sess['msg_icon']     = '&nbsp;';
			}
			else
			{
				$sess['member_name']  .= ' '.$sess['ip_address'];
			}
		}
		
		return $this->ipsclass->compiled_templates['skin_online']->show_row($sess);
	}
        
}

?>