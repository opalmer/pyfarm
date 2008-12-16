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
|   > $Date: 2007-05-11 17:54:11 -0400 (Fri, 11 May 2007) $
|   > $Revision: 994 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Show all the members
|   > Module written by Matt Mecham
|   > Date started: 20th February 2002
|
|	> Module Version Number: 1.0.0
|   > DBA Checked: Wed 19 May 2004
|   > Quality Checked: Wed 15 Sept. 2004
+--------------------------------------------------------------------------
*/


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class memberlist
{
	# Classes
	var $ipsclass;
	
	# Others
    var $output     = "";
    var $page_title = "";
    var $nav        = array();
    var $html       = "";
   
    var $first       = 0;
    var $max_results = 10;
    var $sort_key    = 'members_display_name';
    var $sort_order  = 'asc';
    var $filter      = 'ALL';
    
    var $mem_titles = array();
    var $mem_groups = array();
    
    var $ucp_html   = "";
    var $topic      = "";
    
    /*-------------------------------------------------------------------------*/
	// Auto-run
	/*-------------------------------------------------------------------------*/

    function auto_run()
    {
		//-----------------------------------------
    	// Require the HTML and language modules
    	//-----------------------------------------
    	
		$this->ipsclass->load_language('lang_mlist');
    	$this->ipsclass->load_template('skin_mlist');

		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$see_groups     = array();
		$the_filter     = array( 'ALL' => $this->ipsclass->lang['show_all'] );
		$the_members    = array();
		$custom_fields  = array();
		$checked        = $this->ipsclass->input['photoonly'] == 1 ? 'checked="checked"' : "";
		$query          = array();
    	$url            = array();
    	$query_string   = "";
		$error          = 0;
		$quick_jump     = "";
		$pp_rating_real = intval( $this->ipsclass->input['pp_rating_real'] );
		$pp_gender      = substr( trim( $this->ipsclass->input['pp_gender'] ), 0, 10 );
		
		$this->ipsclass->input['showall'] = intval($this->ipsclass->input['showall']);
		
		if( $this->ipsclass->input['showall'] )
		{
			$url[] = 'showall=' . $this->ipsclass->input['showall'];
		}
		
		$this->first 	   = intval($this->ipsclass->input['st']);
		$this->max_results = isset( $this->ipsclass->input['max_results'] ) ? $this->ipsclass->input['max_results'] : '20';
		$this->sort_key    = isset( $this->ipsclass->input['sort_key'] )    ? $this->ipsclass->input['sort_key']    : 'members_display_name';
		$this->sort_order  = isset( $this->ipsclass->input['sort_order'] )  ? $this->ipsclass->input['sort_order']  : 'asc';
		$this->filter      = isset( $this->ipsclass->input['filter'] )      ? ( $this->ipsclass->input['filter'] == 'ALL' ? 'ALL' : intval( $this->ipsclass->input['filter'] ) ) : 'ALL';
    	
    	if ( $this->ipsclass->member['g_mem_info'] != 1 )
 		{
 			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission' ) );
    	}
    	
    	//-----------------------------------------
    	// Get the member groups, member titles stuff
    	//-----------------------------------------
    	
    	foreach( $this->ipsclass->cache['group_cache'] as $row )
    	{
    		if ( $row['g_hide_from_list'] )
    		{
	    		if ( ! ( $this->ipsclass->member['g_access_cp'] AND $this->ipsclass->input['showall'] ) )
	    		{
    				continue;
				}
    		}
    		
    		$see_groups[] = $row['g_id'];
    		
    		$this->mem_groups[ $row['g_id'] ] = array( 'TITLE'  => $row['g_title'],
    												   'ICON'   => $row['g_icon'] );
    	}
    	
    	foreach( $this->mem_groups as $id => $data )
    	{
    		if ( $id == $this->ipsclass->vars['guest_group'] )
    		{
    			continue;
    		}
    		
    		$the_filter[ $id ] = $data['TITLE'];
    	}
    	
    	$group_string = implode( ",", $see_groups );
    	
    	//-----------------------------------------
    	// Init some arrays
    	//-----------------------------------------
    	
    	$the_sort_key = array( 'members_display_name'  => 'sort_by_name',
    						   'posts'   			   => 'sort_by_posts',
    						   'joined' 			   => 'sort_by_joined',
							   'members_profile_views' => 'm_dd_views',
    						 );
    						 
    	$the_max_results = array( 
    							  20  => '20',
    							  40  => '40',
    							  60  => '60',
    						    );
    						    
    	$the_sort_order = array(  'desc' => 'descending_order',
    							  'asc'  => 'ascending_order',
    						   );
    						   
    	//-----------------------------------------
    	// Start the form stuff
    	//-----------------------------------------
    						   
    	$filter_html      = "<select name='filter' class='forminput'>\n";
    	$sort_key_html    = "<select name='sort_key' class='forminput'>\n";
    	$max_results_html = "<select name='max_results' class='forminput'>\n";
    	$sort_order_html  = "<select name='sort_order' class='forminput'>\n";
    	
    	foreach ($the_sort_order as $k => $v)
    	{
			$sort_order_html .= $k == $this->sort_order ? "<option value='$k' selected='selected'>" . $this->ipsclass->lang[ $the_sort_order[ $k ] ] . "</option>\n"
											            : "<option value='$k'>"          . $this->ipsclass->lang[ $the_sort_order[ $k ] ] . "</option>\n";
		}
     	foreach ($the_filter as $k => $v)
     	{
			$filter_html .= $k == $this->filter  ? "<option value='$k' selected='selected'>"         . $the_filter[ $k ] . "</option>\n"
											     : "<option value='$k'>"          . $the_filter[ $k ] . "</option>\n";
		}   	
    	foreach ($the_sort_key as $k => $v)
    	{
			$sort_key_html .= $k == $this->sort_key ? "<option value='$k' selected='selected'>"     . $this->ipsclass->lang[ $the_sort_key[ $k ] ] . "</option>\n"
											        : "<option value='$k'>"          . $this->ipsclass->lang[ $the_sort_key[ $k ] ] . "</option>\n";
		}    	
    	foreach ($the_max_results as $k => $v)
    	{
			$max_results_html .= $k == $this->max_results ? "<option value='$k' selected='selected'>". $the_max_results[ $k ] . "</option>\n"
											               : "<option value='$k'>"          . $the_max_results[ $k ] . "</option>\n";
		}
		
		$this->ipsclass->lang['sorting_text'] = str_replace( "<#FILTER#>"      , $filter_html."</select>"     , $this->ipsclass->lang['sorting_text'] );
    	$this->ipsclass->lang['sorting_text'] = str_replace( "<#SORT_KEY#>"    , $sort_key_html."</select>"   , $this->ipsclass->lang['sorting_text'] );
    	$this->ipsclass->lang['sorting_text'] = str_replace( "<#SORT_ORDER#>"  , $sort_order_html."</select>" , $this->ipsclass->lang['sorting_text'] );
    	$this->ipsclass->lang['sorting_text'] = str_replace( "<#MAX_RESULTS#>" , $max_results_html."</select>", $this->ipsclass->lang['sorting_text'] );
    	
    	if ( ! isset($the_sort_key[ $this->sort_key ]) )       $error = 1;
    	if ( ! isset($the_sort_order[ $this->sort_order ]) )   $error = 1;
    	if ( ! isset($the_filter[ $this->filter ]) )           $error = 1;
    	if ( ! isset($the_max_results[ $this->max_results ]) ) $this->max_results = 20;
    	
    	//-----------------------------------------
    	// Error?
    	//-----------------------------------------
    	
    	if ($error == 1 )
    	{
    		if ( $this->ipsclass->input['b'] == 1 ) 
    		{
    			$this->ipsclass->Error( array( LEVEL=> 1, MSG =>'ml_error') );
    		}
    		else
    		{
    			$this->ipsclass->Error( array( LEVEL=> 5, MSG =>'incorrect_use') );
    		}
    	}
    	
    	//-----------------------------------------
    	// Quick form?
    	//-----------------------------------------
    	
    	for ( $i = 65; $i <= 90; $i++ )
    	{
    		$letter      = strtolower(chr($i));
    		$selected    = isset($this->ipsclass->input['quickjump']) AND $this->ipsclass->input['quickjump'] == $letter ? ' selected="selected"' : '';
    		$quick_jump .= $this->ipsclass->compiled_templates['skin_mlist']->mlist_quick_jump_entry( $letter, $selected );
    	}
    	
    	//-----------------------------------------
    	// Get custom profile information
    	//-----------------------------------------
		
		$custom_fields = "";
		
		require_once( ROOT_PATH.'sources/classes/class_custom_fields.php' );
		$this->ipsclass->custom_fields = new custom_fields( $this->ipsclass->DB );
		
		$this->ipsclass->custom_fields->member_id  	= $this->ipsclass->member['id'];
		$this->ipsclass->custom_fields->cache_data 	= $this->ipsclass->cache['profilefields'];
		$this->ipsclass->custom_fields->admin      	= intval($this->ipsclass->member['g_access_cp']);
		$this->ipsclass->custom_fields->supmod     	= intval($this->ipsclass->member['g_is_supmod']);		
    	$this->ipsclass->custom_fields->mem_data_id	= 0;
    	$this->ipsclass->custom_fields->mem_list	= 1;
    	
    	$this->ipsclass->custom_fields->init_data();
    	$this->ipsclass->custom_fields->parse_to_edit();
    	
    	//-----------------------------------------
    	// Quick jump rehash...
    	//-----------------------------------------
    	
    	if ( isset($this->ipsclass->input['qjbutton']) AND $this->ipsclass->input['qjbutton'] 
    		 AND isset($this->ipsclass->input['quickjump']) AND $this->ipsclass->input['quickjump'] )
    	{
    		$this->ipsclass->input['name_box'] = 'begins';
    		$this->ipsclass->input['name']     = $this->ipsclass->input['quickjump'];
    	}
    	
    	//-----------------------------------------
    	// Member Groups...
    	//-----------------------------------------
    	
    	if ($this->filter != 'ALL')
    	{
    		if ( ! preg_match( "/(^|,)".$this->filter."(,|$)/", $group_string ) )
    		{
    			$query[] = "m.mgroup IN($group_string)";
    		}
    		else
    		{
    			$query[] = "m.mgroup='".$this->filter."' ";
    		}
    	}
    	
    	//-----------------------------------------
    	// NOT IN Member Groups...
    	//-----------------------------------------
    	
    	if ( is_array( $this->ipsclass->cache['group_cache'] ) and count ( $this->ipsclass->cache['group_cache'] ) )
    	{
    		$hide_ids = array();
    		
    		foreach( $this->ipsclass->cache['group_cache'] as $data )
    		{
    			if ( $data['g_hide_from_list'] )
    			{
		    		if( !($this->ipsclass->member['g_access_cp'] && $this->ipsclass->input['showall']) )
		    		{
	    				$hide_ids[] = $data['g_id'];
					}
    			}
    		}
    		
    		if ( count( $hide_ids ) )
    		{
    			$query[] = "m.mgroup NOT IN(".implode( ",", $hide_ids ).")";
    		}
    	}
    	
    	//-----------------------------------------
    	// Build query
    	//-----------------------------------------
    	
    	$dates = array( 'lastpost', 'lastvisit', 'joined' );
    	
    	$mapit = array( 'aim'       => 'me.aim_name',
    					'yahoo'     => 'me.yahoo',
    					'icq'       => 'me.icq_number',
    					'msn'       => 'me.msnname',
    					'posts'     => 'm.posts',
    					'joined'    => 'm.joined',
    					'lastpost'  => 'm.last_post',
    					'lastvisit' => 'm.last_visit',
    					'signature' => 'me.signature',
    					'homepage'  => 'me.website',
    					'name'      => 'm.name',
    					'photoonly' => 'pp.pp_main_photo',
    				  );
    	
    	//-----------------------------------------
    	// Do search
    	//-----------------------------------------
    	
    	foreach( $mapit as $in => $tbl )
    	{
	    	$this->ipsclass->input[ $in ] = isset($this->ipsclass->input[ $in ]) ? $this->ipsclass->input[ $in ] : '';
     		$inbit = $this->ipsclass->parse_clean_value(trim(urldecode(stripslashes($this->ipsclass->input[ $in ]))));
    		
    		$url[] = $in.'='.$this->ipsclass->input[ $in ];
    		
    		//-----------------------------------------
    		// Name...
    		//-----------------------------------------
    		
    		if ( $in == 'name' and $inbit != "" )
			{
				if ( $this->ipsclass->input['name_box'] == 'begins' )
				{
					$query[] = "m.members_l_display_name LIKE '".$inbit."%'";
				}
				else
				{
					$query[] = "m.members_l_display_name LIKE '%".$inbit."%'";
				}
			}
			else if ( $in == 'posts' and is_numeric($inbit) and intval($inbit) > -1 )
			{
				$ltmt = $this->ipsclass->input[ $in .'_ltmt' ] == 'lt' ? '<' : '>';
				$query[]  = $tbl. ' '.$ltmt.' '.intval($inbit);
				$url[]    = $in .'_ltmt=' . $this->ipsclass->input[ $in .'_ltmt' ];
			}
			else if ( in_array( $in, $dates ) and $inbit )
			{
				list( $month, $day, $year ) = explode( '-', $this->ipsclass->input[ $in ] );
				
				$month = intval($month);
				$day   = intval($day);
				$year  = intval($year);
				
				if ( ! checkdate( $month, $day, $year ) )
				{
					continue;
				}
				
				$time_int = mktime( 0, 0 ,0,$month, $day, $year );
				
				$ltmt = $this->ipsclass->input[ $in .'_ltmt' ] == 'lt' ? '<' : '>';
				
				$query[]  = $tbl. ' '.$ltmt.' '.$time_int;
				$url[]    = $in .'_ltmt=' . $this->ipsclass->input[ $in .'_ltmt' ];
			}
			else if ( $in == 'photoonly' )
			{
				if ( $this->ipsclass->input['photoonly'] == 1 )
				{
					$query[] = $tbl. "<> ''";
				}
			}
			else if ( $inbit != "" )
			{
				$query[] = $tbl. " LIKE '%{$inbit}%'";
			}	
    	}
    	
    	//-----------------------------------------
    	// Custom fields?
    	//-----------------------------------------
    	
    	if ( count( $this->ipsclass->custom_fields->out_fields ) )
    	{
    		foreach( $this->ipsclass->custom_fields->out_fields as $id => $data )
    		{
    			if ( isset($this->ipsclass->input['field_'.$id]) AND $this->ipsclass->input['field_'.$id] )
    			{
    				$query[] = "p.field_{$id} LIKE '{$this->ipsclass->input['field_'.$id]}%'";
    				$url[]   = "field_{$id}=".$this->ipsclass->input['field_'.$id];
    			}
    		}
    	}

		//-----------------------------------------
		// Rating..
		//-----------------------------------------
		
		if ( $pp_rating_real )
		{
			$query[] = "pp.pp_rating_real > ".$pp_rating_real;
			$url[]   = "pp_rating_real=".$pp_rating_real;
		}
		
		//-----------------------------------------
		// Gender..
		//-----------------------------------------
		
		if ( $pp_gender )
		{
			if ( $pp_gender == 'male' )
			{
				$query[] = "pp.pp_gender='male'";
				$url[]   = "pp_gender=male";
			}
			else if ( $pp_gender == 'female' )
			{
				$query[] = "pp.pp_gender='female'";
				$url[]   = "pp_gender=female";
			}
		}
    	
    	//-----------------------------------------
    	// Finish query
    	//-----------------------------------------
    	
    	$query[] = "m.members_display_name != ''";
    	
    	if ( count( $query ) )
    	{
    		$query_string = implode( " AND ", $query );
    	}
    	
    	//-----------------------------------------
    	// Count...
    	//-----------------------------------------
    	
    	$this->ipsclass->DB->cache_add_query( 'mlist_count', array( 'query' => $query_string ) );
    	$this->ipsclass->DB->cache_exec_query();
    	
    	$max = $this->ipsclass->DB->fetch_row();
    	
    	$this->ipsclass->input['name_box'] = isset($this->ipsclass->input['name_box']) ? $this->ipsclass->input['name_box'] : '';
		
		$pages = $this->ipsclass->build_pagelinks(  array( 'TOTAL_POSS'  => $max['total_members'],
														   'PER_PAGE'    => $this->max_results,
														   'CUR_ST_VAL'  => $this->first,
														   'L_SINGLE'     => "",
														   'L_MULTI'      => $this->ipsclass->lang['pages'],
														   'BASE_URL'     => $this->ipsclass->base_url."&amp;name_box={$this->ipsclass->input['name_box']}&amp;sort_key={$this->sort_key}&amp;sort_order={$this->sort_order}&amp;filter={$this->filter}&amp;act=members&amp;max_results={$this->max_results}&amp;".implode( '&amp;', $url )
														 )
												  );
									   
		//-----------------------------------------
    	// Get custom profile information
    	//-----------------------------------------
    	
    	if ( count( $this->ipsclass->custom_fields->out_fields ) )
    	{
			foreach( $this->ipsclass->custom_fields->out_fields as $id => $data )
			{
				if ( $this->ipsclass->custom_fields->cache_data[ $id ]['pf_type'] == 'drop' )
				{
					$tmp = $this->ipsclass->compiled_templates['skin_mlist']->mlist_custom_field_dropdown( 'field_'.$id, $data );
				}
				else
				{
					$tmp = $this->ipsclass->compiled_templates['skin_mlist']->mlist_custom_field_textinput( 'field_'.$id );
 				}
 				
 				$custom_fields .= $this->ipsclass->compiled_templates['skin_mlist']->mlist_custom_field_entry( $this->ipsclass->custom_fields->field_names[ $id ], $tmp );
			}
		}
		
		//-----------------------------------------
		// START THE LISTING
		//-----------------------------------------
		
		$_count   = 0;
		$_per_row = 4;
		
		if ( $max['total_members'] > 0 )
		{
			$this->ipsclass->DB->cache_add_query( 'mlist_get_members', array( 'query'   => $query_string,
																			  'sort'    => $this->sort_key,
																			  'order'   => $this->sort_order,
																			  'limit_a' => $this->first,
																			  'limit_b' => $this->max_results ) );
			$outer = $this->ipsclass->DB->cache_exec_query();
			
			while ($member = $this->ipsclass->DB->fetch_row($outer) )
			{
				$member['members_display_name'] = $member['members_display_name'] ? $member['members_display_name'] : $member['name'];

				//-----------------------------------------
				// Kludgy.. kludge.. must out in IPB 3.0
				//-----------------------------------------
				
				$member = $this->ipsclass->parse_member( $member, 0, 'skin_mlist' );
				
				# Stop the length check taking into account member title formatting
				$member['_members_display_name'] = $member['members_display_name'];
				
				$member['joined'] = $this->ipsclass->get_date( $member['joined'], 'JOINED' );
				$member['group']  = $this->ipsclass->make_name_formatted( $this->mem_groups[ $member['mgroup'] ]['TITLE'], $member['mgroup'] );
				$member['posts']  = $this->ipsclass->do_number_format($member['posts']);
				
				//-----------------------------------------
				// Bug fix... name-- breaks formatting
				// xhmlt invalid..
				//-----------------------------------------
				
				$member['members_display_name'] = str_replace( '--', '&#45;&#45;', $this->ipsclass->make_name_formatted( $member['members_display_name'], $member['mgroup'] ) );
				
				//-----------------------------------------
				// New row?
				//-----------------------------------------
				
				if ( $_count % $_per_row == 0 AND $_count > 0 )
				{
					$member['_new_row'] = 1;
				}
				
				$_count++;
				
				$the_members[] = $member;
			}
		}
		
		//-----------------------------------------
		// More rows?
		//-----------------------------------------
		
		if ( $_count % $_per_row != 0 )
		{
			for( $i = 0 ; $i < $_per_row ; $i ++ )
			{
				if ( $_count % $_per_row != 0 )
				{
					$the_members[] = array( '_blank' => 1 );
				}
				else
				{
					break;
				}
				
				$_count++;
			}
		}
		
		//-----------------------------------------
		// Print...
		//-----------------------------------------
		
		$this->output .= $this->ipsclass->compiled_templates['skin_mlist']->member_list_show($the_members, $pages, $quick_jump, $checked);
		
		if ( $custom_fields )
		{
			$this->output = str_replace( '<!--CUSTOM_FIELDS-->', $this->ipsclass->compiled_templates['skin_mlist']->mlist_custom_field_wrap($custom_fields), $this->output );
		}
    	
    	//-----------------------------------------
    	// Push to print handler
    	//-----------------------------------------
    	
    	$this->ipsclass->print->add_output( $this->output );
        $this->ipsclass->print->do_output( array( 'TITLE' => $this->ipsclass->lang['page_title'], 'JS' => 0, 'NAV' => array( $this->ipsclass->lang['page_title'] ) ) );
 	}
	
}

?>