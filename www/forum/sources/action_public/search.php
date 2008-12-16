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
|   > Searching procedures
|   > Module written by Matt Mecham
|   > Date started: 24th February 2002
|
|	> Module Version Number: 1.1.0
|   > DBA Checked: Thu 20 May 2004
|   > Quality Checked: Wed 15 Sept. 2004
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class search {

    var $output     		= "";
    var $page_title 		= "";
    var $nav        		= array();
    var $html       		= "";
    
    var $first      		= 0;
    var $row_count			= 0;
    
    var $search_type 		= 'posts';
    var $sort_order  		= 'desc';
    var $sort_key    		= 'last_post';
    var $search_in   		= 'posts';
    var $topic_search_only 	= "";
    var $prune       		= '0';
    var $st_time     		= array();
    var $end_time    		= array();
    var $st_stamp    		= "";
    var $end_stamp   		= "";
    var $result_type 		= "topics";
    var $load_lib    		= 'search_mysql_man';
    var $lib         		= "";
    var $read_array	 		= array();
    
    var $mysql_version   	= "";
	var $true_version    	= "";
	
	// max number of results
	
	var $resultlimit		= 1000;
	var $xml_out			= 0;
	
	var $searchable_forums	= array();
    
    /*-------------------------------------------------------------------------*/
    // Auto run
    /*-------------------------------------------------------------------------*/
    
    function auto_run()
    {
        //-----------------------------------------
        // Using XML?
        //-----------------------------------------
        
        $this->xml_out = isset($this->ipsclass->input['xml']) ? intval( $this->ipsclass->input['xml'] ) : 0;
        
    	//-----------------------------------------
    	// Require the HTML and language modules
    	//-----------------------------------------
    	
		$this->ipsclass->load_language('lang_search');
		$this->ipsclass->load_language('lang_forum' );
    	$this->ipsclass->load_template('skin_search');         
        
    	//-----------------------------------------
    	// Check
    	//-----------------------------------------
    	
		if (! $this->ipsclass->vars['allow_search'])
    	{
			if ( $this->xml_out )
			{
				@header( "Content-type: text/html;charset={$this->ipsclass->vars['gb_char_set']}" );
				print $this->ipsclass->lang['search_off'];
				exit();
			}
			else
			{
    			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'search_off') );
			}
    	}
    	
    	if (! isset($this->ipsclass->member['g_use_search']) OR !$this->ipsclass->member['g_use_search'] )
    	{
			if ( $this->xml_out )
			{
				@header( "Content-type: text/html;charset={$this->ipsclass->vars['gb_char_set']}" );
				print $this->ipsclass->lang['no_xml_permission'];
				exit();
			}
			else
			{	    	
    			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_permission') );
			}
    	}    	
    	
    	if ( $read = $this->ipsclass->my_getcookie('topicsread') )
        {
			$this->read_array = $this->ipsclass->clean_int_array( unserialize(stripslashes($read)) );
        }        
        
        //-----------------------------------------
        // Forum jump
        //-----------------------------------------
        
        $this->ipsclass->forum_jump = $this->ipsclass->build_forum_jump();
    	
    	//-----------------------------------------
		// Get the SQL version.
		//-----------------------------------------
		
		$this->ipsclass->DB->sql_get_version();
		
		$this->true_version  = $this->ipsclass->DB->true_version;
		$this->mysql_version = $this->ipsclass->DB->mysql_version;
		
    	//-----------------------------------------
    	// Sort out the required search library
    	//-----------------------------------------
    	
    	$method = isset($this->ipsclass->vars['search_sql_method']) ? $this->ipsclass->vars['search_sql_method'] : 'man';
    	$sql    = isset($this->ipsclass->vars['sql_driver'])        ? $this->ipsclass->vars['sql_driver']        : 'mysql';
    	
    	$this->load_lib = 'search_'.strtolower($sql).'_'.$method.'.php';
    	
    	require ( ROOT_PATH."sources/lib/".$this->load_lib );
    	
    	$this->base_url = $this->ipsclass->base_url;
    	
    	//-----------------------------------------
    	// Suck in libby
    	//-----------------------------------------
    	
    	$this->lib = new search_lib($this);
    	$this->lib->ipsclass =& $this->ipsclass;
    	
    	$this->first = intval($this->ipsclass->input['st']) >=0 ? intval($this->ipsclass->input['st']) : 0;
    	
    	//-----------------------------------------
    	// What to do?
    	//-----------------------------------------
    	
    	switch($this->ipsclass->input['CODE']) {
    		case '01':
    			$this->do_search();
    			break;
    		case 'getnew':
    			$this->get_new_posts();
    			break;
    		case 'show':
    			$this->show_results();
    			break;
    		case 'getreplied':
    			$this->get_replies();
    			break;
    		case 'lastten':
    			$this->get_last_ten();
    			break;
    		case 'getalluser':
    			$this->get_all_user();
    			break;
    		case 'simpleresults':
    			$this->ipsclass->input['searchsubs'] = 1;
    			$this->show_simple_results();
    			break;
    		case 'explain':
    			$this->show_boolean_explain();
    			break;
    		case 'searchtopic':
    			$this->search_topic();
    			break;
    		case 'gettopicsuser':
    			$this->get_topics_user();
    			break;
    		case 'getactive':
    			$this->ipsclass->input['active'] = 1;
    			$this->get_new_posts();
    			break;
    		default:
    			$this->show_form();
    			break;
    	}
    	
    	// If we have any HTML to print, do so...
    	
    	$this->ipsclass->print->add_output("$this->output");
        $this->ipsclass->print->do_output( array( 'TITLE' => $this->page_title, 'JS' => 0, 'NAV' => $this->nav ) );
 	}
 	
 	/*-------------------------------------------------------------------------*/
	// Do simple search
	/*-------------------------------------------------------------------------*/
	
	function show_simple_results()
	{
		$result = $this->lib->do_simple_search();
    }
    
    /*-------------------------------------------------------------------------*/
	// Search topic
	/*-------------------------------------------------------------------------*/
	
	function search_topic()
	{
		$this->topic_id          = intval($this->ipsclass->input['topic']);
    	$this->topic_search_only = 1;
    	$this->result_type       = 'posts';
    	$this->search_type       = 'posts';
    	$this->search_in         = 'posts';
    	
    	$this->do_search();
    }
    
     /*-------------------------------------------------------------------------*/
	// Get all posts by a member
	/*-------------------------------------------------------------------------*/
 	
 	function get_topics_user()
 	{
		//-----------------------------------------
		// Do we have flood control enabled?
		//-----------------------------------------
		
		if ($this->ipsclass->member['g_search_flood'] > 0)
		{
			$flood_time = time() - $this->ipsclass->member['g_search_flood'];
			
			//-----------------------------------------
			// Get any old search results..
			//-----------------------------------------
			
			$this->ipsclass->DB->simple_construct( array( 'select' => 'id',
										  				  'from'   => 'search_results',
										  				  'where'  => "(member_id='".$this->ipsclass->member['id']."' OR ip_address='".$this->ipsclass->input['IP_ADDRESS']."') AND search_date > '$flood_time'" ) );
			$this->ipsclass->DB->simple_exec();
		
			if ( $this->ipsclass->DB->get_num_rows() )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'search_flood', 'EXTRA' => $this->ipsclass->member['g_search_flood']) );
			}
		}
		
		$this->ipsclass->input['forums'] = 'all';
		
		$forums = $this->get_searchable_forums();
		
		$mid    = intval($this->ipsclass->input['mid']);
		
		//-----------------------------------------
		// Do we have any forums to search in?
		//-----------------------------------------
		
		if ($forums == "")
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_forum') );
		}
		
		if ($mid == "")
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_results' ) );
		}
	
		//-----------------------------------------
		// Get the topic ID's to serialize and store into
		// the database
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'count(*) as count', 'from' => 'topics t', 'where' => "t.approved=1 AND t.forum_id IN($forums) AND t.starter_id=$mid" ) );
		$this->ipsclass->DB->simple_exec();
		
		$row = $this->ipsclass->DB->fetch_row();
	
		$results = intval($row['count']);
		
		//-----------------------------------------
		// Do we have any results?
		//-----------------------------------------
		
		if ( ! $results )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_results' ) );
		}
		
		//-----------------------------------------
		// Cache query
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 't.*, t.title as topic_title',
									  'from'   => 'topics t',
									  'where'  => "t.approved=1 AND t.forum_id IN($forums) AND t.starter_id=$mid",
									  'order'  => "t.last_post DESC" ) );
		
		
		$query_to_cache = $this->ipsclass->DB->cur_query;
		$this->ipsclass->DB->flush_query();
		
		//-----------------------------------------
		// If we are still here, store the data into the database...
		//-----------------------------------------
		
		$unique_id = md5(uniqid(microtime(),1));
		
		$this->ipsclass->DB->do_insert( 'search_results', array (
												  'id'          => $unique_id,
												  'search_date' => time(),
												  'post_max'    => $results,
												  'sort_key'    => $this->sort_key,
												  'sort_order'  => $this->sort_order,
												  'member_id'   => $this->ipsclass->member['id'],
												  'ip_address'  => $this->ipsclass->input['IP_ADDRESS'],
												  'query_cache' => $query_to_cache
										 )        );
		
		$this->ipsclass->boink_it( $this->ipsclass->base_url."act=Search&nav=au&CODE=show&searchid=$unique_id&search_in=topics&result_type=topics" );
	}
    
    /*-------------------------------------------------------------------------*/
	// Get all posts by a member
	/*-------------------------------------------------------------------------*/
 	
 	function get_all_user()
 	{
		//-----------------------------------------
		// Do we have flood control enabled?
		//-----------------------------------------
		
		if ($this->ipsclass->member['g_search_flood'] > 0)
		{
			$flood_time = time() - $this->ipsclass->member['g_search_flood'];
			
			// Get any old search results..
			
			$this->ipsclass->DB->simple_construct( array( 'select' => 'id',
										  'from'   => 'search_results',
										  'where'  => "(member_id='".$this->ipsclass->member['id']."' OR ip_address='".$this->ipsclass->input['IP_ADDRESS']."') AND search_date > '$flood_time'" ) );
			$this->ipsclass->DB->simple_exec();
			
			if ( $this->ipsclass->DB->get_num_rows() )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'search_flood', 'EXTRA' => $this->ipsclass->member['g_search_flood']) );
			}
		}
		
		$this->ipsclass->input['forums'] = 'all';
		
		$forums = $this->get_searchable_forums();
		
		$mid    = intval($this->ipsclass->input['mid']);
		
		//-----------------------------------------
		// Do we have any forums to search in?
		//-----------------------------------------
		
		if ($forums == "")
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_forum') );
		}
		
		if ($mid == "")
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_results' ) );
		}
	
		//-----------------------------------------
		// Get the topic ID's to serialize and store into
		// the database
		//-----------------------------------------
		
		$this->ipsclass->DB->cache_add_query( 'search_get_all_user_count', array( 'mid' => $mid, 'forums' => $forums ) );
		$this->ipsclass->DB->cache_exec_query();
	
		$row = $this->ipsclass->DB->fetch_row();
	
		$results = intval($row['count']);
		
		//-----------------------------------------
		// Do we have any results?
		//-----------------------------------------
		
		if ( ! $results )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_results' ) );
		}
		
		//-----------------------------------------
		// Cache query
		//-----------------------------------------
		
		$this->ipsclass->DB->cache_add_query( 'search_get_all_user_query', array( 'mid' => $mid, 'forums' => $forums ) );
		
		$query_to_cache = $this->ipsclass->DB->cur_query;
		$this->ipsclass->DB->flush_query();
		
		//-----------------------------------------
		// If we are still here, store the data into the database...
		//-----------------------------------------
		
		$unique_id = md5(uniqid(microtime(),1));
		
		$this->ipsclass->DB->do_insert( 'search_results', array (
												 'id'          => $unique_id,
												 'search_date' => time(),
												 'post_max'    => $results,
												 'sort_key'    => $this->sort_key,
												 'sort_order'  => $this->sort_order,
												 'member_id'   => $this->ipsclass->member['id'],
												 'ip_address'  => $this->ipsclass->input['IP_ADDRESS'],
												 'query_cache' => $query_to_cache
										)        );
		
		$this->ipsclass->boink_it( $this->ipsclass->base_url."act=Search&nav=au&CODE=show&searchid=$unique_id&search_in=posts&result_type=posts" );
	}
 	
 	/*-------------------------------------------------------------------------*/
 	// Get new posts
 	/*-------------------------------------------------------------------------*/
 	
 	function get_new_posts()
 	{
		if ( ! $this->ipsclass->member['id'] and ! $this->ipsclass->input['active'] )
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_results' ) );
		}
		
		//-----------------------------------------
		// Do we have flood control enabled?
		//-----------------------------------------
		
		if ($this->ipsclass->member['g_search_flood'] > 0)
		{
			$flood_time = time() - $this->ipsclass->member['g_search_flood'];
			
			// Get any old search results..
			
			$this->ipsclass->DB->simple_construct( array( 'select' => 'id',
										 				  'from'   => 'search_results',
										 				  'where'  => "(member_id='".$this->ipsclass->member['id']."' OR ip_address='".$this->ipsclass->input['IP_ADDRESS']."') AND search_date > '$flood_time'" ) );
			$this->ipsclass->DB->simple_exec();
			
			if ( $this->ipsclass->DB->get_num_rows() )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'search_flood', 'EXTRA' => $this->ipsclass->member['g_search_flood']) );
			}
		}
		
		$last_time = $this->ipsclass->member['last_visit'];
		
		if ( $this->ipsclass->member['members_markers']['board'] > $last_time )
		{
			$last_time = $this->ipsclass->member['members_markers']['board'];
		}
		
		$this->ipsclass->input['nav']    = 'lv';
		
		//-----------------------------------------
		// Are we getting 'active topics'?
		//-----------------------------------------
		
		$this->ipsclass->input['lastdate'] = isset($this->ipsclass->input['lastdate']) ? intval($this->ipsclass->input['lastdate']) : 0;
		
		if ( isset($this->ipsclass->input['active']) AND $this->ipsclass->input['active'] )
		{
			if ( $this->ipsclass->input['lastdate'] )
			{
				$last_time = time() - $this->ipsclass->input['lastdate'];
			}
			else
			{
				$last_time = time() - 86400;
			}
			
			$this->ipsclass->input['nav']    = 'at';
		}
		
		$this->ipsclass->input['forums'] = 'all';
		
		$forums = $this->get_searchable_forums();
		
		//-----------------------------------------
		// Do we have any forums to search in?
		//-----------------------------------------
		
		if ($forums == "")
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_forum') );
		}
		
		//-----------------------------------------
		// Get the topic ID's to serialize and store into
		// the database
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'count(*) as count', 'from' => 'topics', 'where' => "approved=1 AND state != 'link' AND forum_id IN($forums) AND last_post > '".$last_time."'" ) );
		$this->ipsclass->DB->simple_exec();
		
		$row = $this->ipsclass->DB->fetch_row();
		
		$results = intval($row['count']);
		
		//-----------------------------------------
		// Do we have any results?
		//-----------------------------------------
		
		if ( ! $results )
		{
			//$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_results' ) );
		}
		
		//-----------------------------------------
		// Cache query
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 't.*, t.title as topic_title',
													  'from'   => 'topics t',
													  'where'  => "t.approved=1 AND t.state != 'link' AND t.forum_id IN($forums) AND t.last_post > {$last_time}",
													  'order'  => "t.last_post DESC" ) );
		
		
		$query_to_cache = $this->ipsclass->DB->cur_query;
		$this->ipsclass->DB->flush_query();
		
		//-----------------------------------------
		// If we are still here, store the data into the database...
		//-----------------------------------------
		
		$unique_id = md5(uniqid(microtime(),1));
		
		$this->ipsclass->DB->do_insert( 'search_results', array (
												 'id'          => $unique_id,
												 'search_date' => time(),
												 'post_max'    => $results,
												 'sort_key'    => $this->sort_key,
												 'sort_order'  => $this->sort_order,
												 'member_id'   => $this->ipsclass->member['id'],
												 'ip_address'  => $this->ipsclass->input['IP_ADDRESS'],
												 'query_cache' => $query_to_cache
										)        );
		
		$this->ipsclass->boink_it( $this->ipsclass->base_url."act=Search&nav={$this->ipsclass->input['nav']}&CODE=show&searchid=$unique_id&search_in=topics&result_type=topics&lastdate={$this->ipsclass->input['lastdate']}" );
	}
 	
 	/*-------------------------------------------------------------------------*/
 	// Last 10 posts
 	/*-------------------------------------------------------------------------*/
 	
 	function get_last_ten()
 	{
		//-----------------------------------------
		// Do we have flood control enabled?
		//-----------------------------------------
		
		if ($this->ipsclass->member['g_search_flood'] > 0)
		{
			$flood_time = time() - $this->ipsclass->member['g_search_flood'];
			
			// Get any old search results..
			
			$this->ipsclass->DB->simple_construct( array( 'select' => 'id',
										  'from'   => 'search_results',
										  'where'  => "(member_id='".$this->ipsclass->member['id']."' OR ip_address='".$this->ipsclass->input['IP_ADDRESS']."') AND search_date > '$flood_time'" ) );
			$this->ipsclass->DB->simple_exec();
			
			if ( $this->ipsclass->DB->get_num_rows() )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'search_flood', 'EXTRA' => $this->ipsclass->member['g_search_flood']) );
			}
		}
		
		$this->ipsclass->input['forums'] = 'all';
		
		$forums = $this->get_searchable_forums();
		
		$mid    = $this->ipsclass->member['id'];
		
		//-----------------------------------------
		// Do we have any forums to search in?
		//-----------------------------------------
		
		if ($forums == "")
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_forum') );
		}
		
		//-----------------------------------------
		// Cache query
		//-----------------------------------------
		
		$this->ipsclass->DB->cache_add_query( 'search_get_last_ten', array( 'mid' => $mid, 'forums' => $forums ) );
		
		$query_to_cache = $this->ipsclass->DB->cur_query;
		$this->ipsclass->DB->flush_query();
		
		//-----------------------------------------
		// If we are still here, store the data into the database...
		//-----------------------------------------
		
		$unique_id = md5(uniqid(microtime(),1));
		
		$this->ipsclass->DB->do_insert( 'search_results', array (
												 'id'          => $unique_id,
												 'search_date' => time(),
												 'post_max'    => 10,
												 'sort_key'    => $this->sort_key,
												 'sort_order'  => $this->sort_order,
												 'member_id'   => $this->ipsclass->member['id'],
												 'ip_address'  => $this->ipsclass->input['IP_ADDRESS'],
												 'query_cache' => $query_to_cache
										)        );
		
		$this->ipsclass->boink_it( $this->ipsclass->base_url."act=Search&nav=au&CODE=show&searchid=$unique_id&search_in=posts&result_type=posts" );
	}
 	
 	/*-------------------------------------------------------------------------*/
 	// Get all replies
 	/*-------------------------------------------------------------------------*/
 	
 	function get_replies()
 	{
		//-----------------------------------------
		// Do we have flood control enabled?
		//-----------------------------------------
		
		if ($this->ipsclass->member['g_search_flood'] > 0)
		{
			$flood_time = time() - $this->ipsclass->member['g_search_flood'];
			
			// Get any old search results..
			
			$this->ipsclass->DB->simple_construct( array( 'select' => 'id',
										  'from'   => 'search_results',
										  'where'  => "(member_id='".$this->ipsclass->member['id']."' OR ip_address='".$this->ipsclass->input['IP_ADDRESS']."') AND search_date > '$flood_time'" ) );
			$this->ipsclass->DB->simple_exec();
			
			if ( $this->ipsclass->DB->get_num_rows() )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'search_flood', 'EXTRA' => $this->ipsclass->member['g_search_flood']) );
			}
		}
		
		$this->ipsclass->input['forums'] = 'all';
		$this->ipsclass->input['nav']    = 'lv';
		
		$forums = $this->get_searchable_forums();
		
		if ( $this->ipsclass->forum_read[0] > $this->ipsclass->member['last_visit'] )
		{
			$this->ipsclass->member['last_visit'] = $this->ipsclass->forum_read[0];
		}
			
		//-----------------------------------------
		// Do we have any forums to search in?
		//-----------------------------------------
		
		if ($forums == "")
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_forum') );
		}
	
		//-----------------------------------------
		// Get the topic ID's to serialize and store into
		// the database
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'tid',
									  'from'   => 'topics',
									  'where'  => "starter_id={$this->ipsclass->member['id']} AND last_post > {$this->ipsclass->member['last_visit']} AND forum_id IN($forums) AND approved=1" ) );
		$this->ipsclass->DB->simple_exec();
		
		$max_hits = $this->ipsclass->DB->get_num_rows();
		
		$topics  = "";
		
		while ($row = $this->ipsclass->DB->fetch_row() )
		{
			$topics .= $row['tid'].",";
		}
	
		$this->ipsclass->DB->free_result();
		
		$topics  = preg_replace( "/,$/", "", $topics );
		
		//-----------------------------------------
		// Do we have any results?
		//-----------------------------------------
		
		if ($topics == "")
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_results' ) );
		}
		
		//-----------------------------------------
		// If we are still here, store the data into the database...
		//-----------------------------------------
		
		$unique_id = md5(uniqid(microtime(),1));
		
		$this->ipsclass->DB->do_insert( 'search_results', array (
												 'id'         => $unique_id,
												 'search_date'=> time(),
												 'topic_id'   => $topics,
												 'topic_max'  => $max_hits,
												 'sort_key'   => $this->sort_key,
												 'sort_order' => $this->sort_order,
												 'member_id'  => $this->ipsclass->member['id'],
												 'ip_address' => $this->ipsclass->input['IP_ADDRESS'],
										)        );
		
		$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['search_redirect'] , "act=Search&amp;nav=gr&amp;CODE=show&amp;searchid=$unique_id&amp;search_in=posts&amp;result_type=topics" );
		exit();
	}
	
	/*-------------------------------------------------------------------------*/
	// Show pop-up window
	/*-------------------------------------------------------------------------*/
	
	function show_boolean_explain()
 	{
		$this->ipsclass->print->pop_up_window( $this->ipsclass->lang['be_link'], $this->ipsclass->compiled_templates['skin_search']->boolean_explain_page() );
 	}
	
	/*-------------------------------------------------------------------------*/
	// Show main form
	/*-------------------------------------------------------------------------*/
 	
 	function show_form()
 	{
		$the_html = $this->ipsclass->forums->forums_forum_jump(1, 1);

		$init_sel = !$this->ipsclass->input['f'] ? ' selected="selected"' : '';
		
		$forums   = "<select name='forums[]' class='forminput' size='10' multiple='multiple'>\n"
		           ."<option value='all'".$init_sel.">".$this->ipsclass->lang['all_forums']."</option>"
		           . $the_html
		           . "</select>";
		
		if ( $this->ipsclass->input['mode'] == 'simple' )
		{
			if ( $this->ipsclass->vars['search_sql_method'] == 'ftext' )
			{
				$this->output = $this->ipsclass->compiled_templates['skin_search']->simple_form($forums);
			}
			else
			{
				$this->output = $this->ipsclass->compiled_templates['skin_search']->Form($forums);
			}
		}
		else if ( $this->ipsclass->input['mode'] == 'adv' )
		{
			$this->output = $this->ipsclass->compiled_templates['skin_search']->Form($forums);
			
			if ( $this->ipsclass->vars['search_sql_method'] == 'ftext' )
			{
				$this->output = str_replace( "<!--IBF.SIMPLE_BUTTON-->", $this->ipsclass->compiled_templates['skin_search']->form_simple_button(), $this->output );
			}
		}
		else
		{
			// No mode specified..
			
			if ( $this->ipsclass->vars['search_default_method'] == 'simple' )
			{
				if ( $this->ipsclass->vars['search_sql_method'] == 'ftext' )
				{
					$this->output = $this->ipsclass->compiled_templates['skin_search']->simple_form($forums);
				}
				else
				{
					$this->output = $this->ipsclass->compiled_templates['skin_search']->Form($forums);
				}
			}
			else
			{
				// Default..
				
				$this->output = $this->ipsclass->compiled_templates['skin_search']->Form($forums);
				
				if ( $this->ipsclass->vars['search_sql_method'] == 'ftext' )
				{
					$this->output = str_replace( "<!--IBF.SIMPLE_BUTTON-->", $this->ipsclass->compiled_templates['skin_search']->form_simple_button(), $this->output );
				}
			}
		}
		
		if ( ( $this->ipsclass->DB->sql_can_fulltext_boolean() == TRUE ) AND $this->ipsclass->vars['search_sql_method'] == 'ftext' )
		{
			$this->output = str_replace( "<!--IBF.BOOLEAN_EXPLAIN-->", $this->ipsclass->compiled_templates['skin_search']->boolean_explain_link(), $this->output );
		}
		
		$this->page_title = $this->ipsclass->lang['search_title'];
		$this->nav        = array( $this->ipsclass->lang['search_form'] );
 	}
 	
 	/*-------------------------------------------------------------------------*/
 	// DO MAIN SEARCH
 	/*-------------------------------------------------------------------------*/

	function do_search()
	{
		//-----------------------------------------
		// Un-urlencode first
		//-----------------------------------------	
		if( isset($this->ipsclass->input['xml']) AND $this->ipsclass->input['xml'] )
		{
			require (KERNEL_PATH."class_ajax.php");
			$xml_convert = new class_ajax();
			$xml_convert->ipsclass =& $this->ipsclass;
			
			$this->ipsclass->input['keywords'] = $xml_convert->convert_and_make_safe($this->ipsclass->input['keywords']);
		}
			
		//-----------------------------------------
		// Do we have flood control enabled?
		//-----------------------------------------
		
		if ($this->ipsclass->member['g_search_flood'] > 0)
		{
			$flood_time = time() - $this->ipsclass->member['g_search_flood'];
			
			// Get any old search results..
			
			$this->ipsclass->DB->simple_construct( array( 'select' => 'id',
														  'from'   => 'search_results',
														  'where'  => "(member_id='".$this->ipsclass->member['id']."' OR ip_address='".$this->ipsclass->input['IP_ADDRESS']."') AND search_date > '$flood_time'" ) );
			$this->ipsclass->DB->simple_exec();
			
			if ( $this->ipsclass->DB->get_num_rows() )
			{
				if ( $this->xml_out )
				{
					@header( "Content-type: text/html;charset={$this->ipsclass->vars['gb_char_set']}" );
					print sprintf( $this->ipsclass->lang['xml_flood'], $this->ipsclass->member['g_search_flood'] );
					exit();
				}
				else
				{
					$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'search_flood', 'EXTRA' => $this->ipsclass->member['g_search_flood']) );
				}
			}
		}

		//-----------------------------------------
		// init main search
		//-----------------------------------------
		
		$result = $this->lib->do_main_search();
		
		//-----------------------------------------
		// Do we have any results?
		//-----------------------------------------
		
		if ($result['topic_id'] == "" and $result['post_id'] == "")
		{
			if ( $this->xml_out )
			{
				@header( "Content-type: text/html;charset={$this->ipsclass->vars['gb_char_set']}" );
				print sprintf( $this->ipsclass->lang['xml_nomatches'], $result['keywords'] );
				exit();
			}
			else
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_results' ) );
			}
		}
		
		//-----------------------------------------
		// If we are still here, store the data into the database...
		//-----------------------------------------
		
		$unique_id = md5(uniqid(microtime(),1));
		$url       = "act=Search&amp;CODE=show&amp;searchid=".$unique_id."&amp;search_in=".$this->search_in."&amp;result_type=".$this->result_type."&amp;highlite=".urlencode(trim($result['keywords']));
		
		$this->ipsclass->DB->do_insert( 'search_results', array (
																   'id'          => $unique_id,
																   'search_date' => time(),
																   'topic_id'    => $result['topic_id'],
																   'topic_max'   => $result['topic_max'],
																   'sort_key'    => $this->sort_key,
																   'sort_order'  => $this->sort_order,
																   'member_id'   => $this->ipsclass->member['id'],
																   'ip_address'  => $this->ipsclass->input['IP_ADDRESS'],
																   'post_id'     => $result['post_id'],
																   'post_max'    => $result['post_max'],
																   'query_cache' => $result['query_cache'],
													   )        );
		
		if ( $this->xml_out )
		{
			$result['post_max'] = $result['post_max'] < 1 ? 1 : $result['post_max'];
			
			@header( "Content-type: text/html;charset={$this->ipsclass->vars['gb_char_set']}" );
			print sprintf( $this->ipsclass->lang['xml_matches'], preg_replace( "#^[\+\-]#", "", $result['keywords'] ), $result['post_max'], $this->ipsclass->base_url.$url );
			exit();
		}
		else
		{
			$this->ipsclass->print->redirect_screen( $this->ipsclass->lang['search_redirect'] , $url );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Show Results
	// Shows the results of the search
	/*-------------------------------------------------------------------------*/
	
	function show_results()
	{
		$this->cached_query   = 0;
		$this->cached_matches = 0;
	
       	//-----------------------------------------
       	// Grab forums lib
       	//-----------------------------------------
       	
       	require_once( ROOT_PATH."sources/action_public/forums.php" );
       	$this->forums = new forums();
       	$this->forums->ipsclass =& $this->ipsclass;
       	$this->forums->init();
       	
       	//-----------------------------------------
       	// Start...
       	//-----------------------------------------
		
        $this->result_type  = $this->ipsclass->input['result_type'];
        $this->search_in    = $this->ipsclass->input['search_in'];
		
		//-----------------------------------------
		// We have a search ID, so lets get the parsed results.
		//-----------------------------------------
		
		$this->unique_id = $this->ipsclass->input['searchid'];
		
		if ($this->unique_id == "")
		{
			$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_results' ) );
		}
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*',
									  				  'from'   => 'search_results',
									  				  'where'  => "id='{$this->unique_id}'" ) );
		$this->ipsclass->DB->simple_exec();
		
		$sr = $this->ipsclass->DB->fetch_row();
		
		$this->sort_order   = $sr['sort_order'];
		$this->sort_key     = $sr['sort_key'];
		$this->more_results = $sr['query_cache'] == 1 ? 1 : 0;
		
		//-----------------------------------------
		// Cached query or PID/TID list?
		// query_cache == 1 if more than 300 results
		//-----------------------------------------
		
		if ( $sr['query_cache'] and $sr['query_cache'] != 1 )
		{
			$this->cached_query   = $sr['query_cache'];
			$this->cached_matches = $sr['post_max'];
		}
		else
		{
			$topics         = $sr['topic_id'];
			$topic_max_hits = $sr['topic_max'];
			$posts          = $sr['post_id'];
			$post_max_hits  = $sr['post_max'];
			
			//-----------------------------------------
			// Build array
			//-----------------------------------------
			
			$topic_array = array();
			$post_array  = array();
			
			if ( $topics )
			{
				foreach( explode( ",", $topics ) as $t )
				{
					$topic_array[ $t ] = $t;
				}
			}
			
			if ( $posts )
			{
				foreach( explode( ",", $posts ) as $t )
				{
					$post_array[ $t ] = $t;
				}
			}

			//-----------------------------------------
			// Anything left to show?
			//-----------------------------------------
			
			if ( ! $topics and ! $posts )
			{
				$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_results' ) );
			}
		}
		
		$this->ipsclass->input['highlite'] = $this->ipsclass->input['highlite'] ? $this->ipsclass->input['highlite'] : $this->ipsclass->input['hl'];
		
		$url_words = $this->convert_highlite_words( (isset($this->ipsclass->input['highlite']) AND $this->ipsclass->input['highlite']) ? $this->ipsclass->input['highlite'] : '' );
		
		if ( $this->result_type == 'topics' )
		{ 
			//-----------------------------------------
			// CACHED QUERY?
			//-----------------------------------------
			
			if ( $this->cached_query )
			{
				$this->output .= $this->start_page($this->cached_matches);
				
				$this->ipsclass->DB->cur_query = $this->cached_query;
				$this->ipsclass->DB->simple_limit_with_check($this->first, "25");
				$this->cached_query = $this->ipsclass->DB->cur_query;
				
				$this->ipsclass->DB->prefix_changed = 1;
				$outer = $this->ipsclass->DB->query( $this->cached_query );
				$this->ipsclass->DB->prefix_changed = 0;
				$this->ipsclass->DB->flush_query();				
			}
			//-----------------------------------------
			// PID / TID
			//-----------------------------------------
			
			else if ($this->search_in == 'titles')
			{
				/*if ($posts AND !$topics)
				{
					$this->ipsclass->DB->simple_construct( array( 'select' => 'topic_id', 'from' => 'posts', 'where' => "pid IN({$posts})" ) );
					$this->ipsclass->DB->simple_exec();
				
					while ( $pr = $this->ipsclass->DB->fetch_row() )
					{
						$topic_array[ $pr['topic_id'] ] = $pr['topic_id'];
					}
					
					$topics         = implode( ",", $topic_array );
					$topic_max_hits = count( $topic_array );
				}*/
								
				if ( ! $topics )
				{
					$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_results' ) );
				}
			
				$this->output .= $this->start_page($topic_max_hits);
				
				$this->ipsclass->DB->simple_construct( array( 'select' => 't.*',
															  'from'   => 'topics t',
															  'where'  => "t.tid IN({$topics})",
															  'order'  => "t.pinned DESC, t.".$this->sort_key." ".$this->sort_order,
															  'limit'  => array( $this->first, 25 )
													 )      );
				$outer = $this->ipsclass->DB->simple_exec();
			}
			else
			{
				//-----------------------------------------
				// Add posts to the mix
				//-----------------------------------------
				
				if ($posts)
				{
					$this->ipsclass->DB->simple_construct( array( 'select' => 'topic_id', 'from' => 'posts', 'where' => "pid IN({$posts})" ) );
					$this->ipsclass->DB->simple_exec();
				
					while ( $pr = $this->ipsclass->DB->fetch_row() )
					{
						$topic_array[ $pr['topic_id'] ] = $pr['topic_id'];
					}
					
					$topics         = implode( ",", $topic_array );
					$topic_max_hits = count( $topic_array );
				}
				
				$this->output .= $this->start_page($topic_max_hits);
				
				$this->ipsclass->DB->simple_construct( array( 'select' => 't.*',
															  'from'   => 'topics t',
															  'where'  => "t.tid IN({$topics})",
															  'order'  => "t.pinned DESC, t.".$this->sort_key." ".$this->sort_order,
															  'limit'  => array( $this->first, 25 )
													 )      );
				$outer = $this->ipsclass->DB->simple_exec();
			}
			
			//-----------------------------------------
			// PRINT: Any returned rows?
			//-----------------------------------------
			
			if ( $this->ipsclass->DB->get_num_rows($outer) )
			{
				$topic_ids  = array();
				$the_topics = array();
				
				while ( $row = $this->ipsclass->DB->fetch_row($outer) )
				{
					$row['keywords'] = $url_words;
					
					$the_topics[$row['tid']] = $row;
					$topic_ids[] = $row['tid'];
				}
				
				if ( ($this->ipsclass->vars['show_user_posted'] == 1) and ($this->ipsclass->member['id']) and ( count($topic_ids) ) )
				{
					$this->ipsclass->DB->build_query( array( 'select' => 'author_id, topic_id',
															 'from'   => 'posts',
															 'where'  => "author_id=".$this->ipsclass->member['id']." AND topic_id IN(".implode( ",", $topic_ids ).")",
														)      );
											  
					$this->ipsclass->DB->simple_exec();
					
					while( $p = $this->ipsclass->DB->fetch_row() )
					{
						if ( is_array( $the_topics[ $p['topic_id'] ] ) )
						{
							$the_topics[ $p['topic_id'] ]['author_id'] = $p['author_id'];
						}
					}
				}
				
				foreach( $the_topics as $row )
				{
					$this->output   .= $this->ipsclass->compiled_templates['skin_search']->RenderRow( $this->parse_entry($row) );
				}
			}
			else
			{
				if ( ! $this->ipsclass->input['lastdate'] )
				{
					$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_results' ) );
				}
				else
				{
					$this->output .= $this->ipsclass->compiled_templates['skin_search']->no_results_row(); 
				}
			}
			
			//-----------------------------------------
			// PRINT: End the page
			//-----------------------------------------
			
			$this->output .= $this->ipsclass->compiled_templates['skin_search']->end_results_table(array( 'SHOW_PAGES' => $this->links ));
		}
		
		//-----------------------------------------
		// Results as posts...
		//-----------------------------------------
		
		else
		{
			// When viewing as posts, sort by post date not topic last post
			
			$this->sort_key = $this->sort_key == 'last_post' ? 'p.post_date' : $this->sort_key;
			
			$this->sort_key = ( strpos( $this->sort_key, "t." ) === FALSE AND strpos( $this->sort_key, "p." ) === FALSE )
								? 't.' . $this->sort_key : $this->sort_key;
			//-----------------------------------------
			// Grab topic lib
			//-----------------------------------------
			
			require_once( ROOT_PATH.'sources/action_public/topics.php' );
			$this->topics = new topics();
			$this->topics->ipsclass =& $this->ipsclass;
			$this->topics->topic['tid'] = 0;
			$this->topics->topic['forum_id'] = 0;			
			$this->topics->topic_init();

			$attach_pids = array();
			
			//-----------------------------------------
			// CACHED QUERY?
			//-----------------------------------------
			
			if ( $this->cached_query )
			{
				$this->output .= $this->start_page($this->cached_matches, 1);
				
				$this->ipsclass->DB->cur_query = $this->cached_query;
				$this->ipsclass->DB->simple_limit_with_check($this->first, "25");
				$this->cached_query = $this->ipsclass->DB->cur_query;
				
				$outer = $this->ipsclass->DB->query( $this->cached_query );
				$this->ipsclass->DB->flush_query();				
			}
			//-----------------------------------------
			// PID / TID
			//-----------------------------------------
			
			else
			{
				//-----------------------------------------
				// Start...
				//-----------------------------------------
				
				if ($this->search_in == 'titles')
				{
					if ($posts AND !$topics)
					{
						$this->ipsclass->DB->simple_construct( array( 'select' => 'topic_id', 'from' => 'posts', 'where' => "pid IN({$posts})" ) );
						$this->ipsclass->DB->simple_exec();
					
						while ( $pr = $this->ipsclass->DB->fetch_row() )
						{
							$topic_array[ $pr['topic_id'] ] = $pr['topic_id'];
						}
						
						$topics         = implode( ",", $topic_array );
						$topic_max_hits = count( $topic_array );
					}
					
					if ( ! $topics )
					{
						$this->ipsclass->Error( array( 'LEVEL' => 1, 'MSG' => 'no_search_results' ) );
					}
				
					$this->output .= $this->start_page($topic_max_hits, 1);
					
					$this->ipsclass->DB->build_query( array(	'select'	=> 't.*, t.posts as topic_posts, t.title as topic_title',
																'from'		=> array( 'topics' => 't' ),
																'where'		=> "t.tid IN({$topics})",
																'limit'		=> array( $this->first, '25' ),
																'order'		=> $this->sort_key . ' ' . $this->sort_order,
																'add_join'	=> array(
																					array(
																							'select'	=> 'p.pid, p.author_id, p.author_name, p.post_date, p.post, p.post_htmlstate, p.queued',
																							'from'		=> array( 'posts' => 'p' ),
																							'where'		=> 'p.topic_id=t.tid AND p.new_topic=1',
																							'type'		=> 'left'
																						),
																					array( 
																							'select'	=> 'm.*',
																							'from'		=> array( 'members' => 'm' ),
																							'where'		=> 'm.id=p.author_id',
																							'type'		=> 'left'
																						),	
																					array( 
																							'select'	=> 'me.*',
																							'from'		=> array( 'member_extra' => 'me' ),
																							'where'		=> 'me.id=p.author_id',
																							'type'		=> 'left'
																						),	
																					array( 
																							'select'	=> 'pp.*',
																							'from'		=> array( 'profile_portal' => 'pp' ),
																							'where'		=> 'pp.pp_member_id=p.author_id',
																							'type'		=> 'left'
																						),	
																					)
													)		);

					$outer = $this->ipsclass->DB->exec_query();
				}
				else
				{
					//-----------------------------------------
					// Add Topics
					//-----------------------------------------
					
					if ( $topics )
					{
						$this->ipsclass->DB->simple_construct( array( 'select' => 'pid', 'from' => 'posts', 'where' => "topic_id IN({$topics}) AND new_topic=1" ) );
						$this->ipsclass->DB->simple_exec();
					
						while ( $pr = $this->ipsclass->DB->fetch_row() )
						{
							$post_array[ $pr['pid'] ] = $pr['pid'];
						}
						
						$posts         = implode( ",", $post_array );
						$post_max_hits = count( $post_array );
					}
					
					$this->output .= $this->start_page($post_max_hits, 1);
					
					$this->ipsclass->DB->build_query( array(	'select'	=> 'p.pid, p.author_id, p.author_name, p.post_date, p.post, p.post_htmlstate, p.queued',
																'from'		=> array( 'posts' => 'p' ),
																'where'		=> "p.pid IN({$posts})",
																'limit'		=> array( $this->first, '25' ),
																'order'		=> $this->sort_key . ' ' . $this->sort_order,
																'add_join'	=> array(
																					array(
																							'select'	=> 't.*, t.posts as topic_posts, t.title as topic_title',
																							'from'		=> array( 'topics' => 't' ),
																							'where'		=> 't.tid=p.topic_id',
																							'type'		=> 'left'
																						),
																					array( 
																							'select'	=> 'm.*',
																							'from'		=> array( 'members' => 'm' ),
																							'where'		=> 'm.id=p.author_id',
																							'type'		=> 'left'
																						),	
																					array( 
																							'select'	=> 'me.*',
																							'from'		=> array( 'member_extra' => 'me' ),
																							'where'		=> 'me.id=p.author_id',
																							'type'		=> 'left'
																						),	
																					array( 
																							'select'	=> 'pp.*',
																							'from'		=> array( 'profile_portal' => 'pp' ),
																							'where'		=> 'pp.pp_member_id=p.author_id',
																							'type'		=> 'left'
																						),	
																					)
													)		);

					$outer = $this->ipsclass->DB->exec_query();
				}
			}
			
			while ( $row = $this->ipsclass->DB->fetch_row($outer) )
			{
				$row['keywords']  = $url_words;
				$row['post_date'] = $this->ipsclass->get_date( $row['post_date'],'LONG' );
				
				if ( $row['queued'] or ($row['topic_firstpost'] == $row['pid'] and $row['approved'] != 1) )
				{
					$row['post_css'] = $this->row_count % 2 ? 'post1shaded' : 'post2shaded';
				}
				else
				{
					$row['post_css'] = $this->row_count % 2 ? 'post1' : 'post2';
				}
				
				//-----------------------------------------
				// Attachments?
				//-----------------------------------------
				
				//if ( strstr( $row['post'], '[attachment=' ) )
				//{
					$attach_pids[] = $row['pid'];
				//}
				
				//-----------------------------------------
				// Parse Member
				//-----------------------------------------
				
				$member = $this->ipsclass->parse_member( $row, 0, 'skin_search' );
				
				if ( $member['id'] )
				{
					$member['_members_display_name'] = "<a href='{$this->base_url}showuser={$member['id']}'>{$member['members_display_name_short']}</a>";
				} 
						
				$post	= $this->parse_entry($row, 1);
				
				//-----------------------------------------
				// Do word wrap?
				//-----------------------------------------
				
				$this->output .= $this->ipsclass->compiled_templates['skin_search']->RenderPostRow( $post, $member );
				
				//-----------------------------------------
				// Ignoring member?
				//-----------------------------------------
								
				if ( $this->ipsclass->member['ignored_users'] )
				{
					if ( strstr( $this->ipsclass->member['ignored_users'], ','.$member['id'].',' ) and $this->ipsclass->input['p'] != $row['pid'] )
					{
						if ( ! strstr( $this->ipsclass->vars['cannot_ignore_groups'], ','.$member['mgroup'].',' ) )
						{
							$this->output .= $this->ipsclass->compiled_templates['skin_search']->render_row_hidden( $post, $member );
							continue;
						}
					}
				}			
			}
			
			//-----------------------------------------
			// Add in attachments?
			//-----------------------------------------
			
			if ( count( $attach_pids ) )
			{
				if ( ! is_object( $this->class_attach ) )
				{
					//-----------------------------------------
					// Grab render attach class
					//-----------------------------------------

					require_once( ROOT_PATH . 'sources/classes/attach/class_attach.php' );
					$this->class_attach           =  new class_attach();
					$this->class_attach->ipsclass =& $this->ipsclass;
					
					$this->class_attach->type  = 'post';
					$this->class_attach->init();
				}

				$this->output = $this->class_attach->render_attachments( $this->output, $attach_pids );
			}
			
			$this->output .= $this->ipsclass->compiled_templates['skin_search']->end_results_table(array( 'SHOW_PAGES' => $this->links ), 1 );
		}
		
		$this->page_title = $this->ipsclass->lang['search_results'];
		
		if ( $this->ipsclass->input['nav'] == 'lv' )
		{
			if ( $this->ipsclass->input['lastdate'] )
			{
				$this->nav = array( $this->ipsclass->lang['nav_au'] );
			}
			else
			{
				$this->nav = array( $this->ipsclass->lang['nav_since_lv'] );
			}
		}
		else if ( $this->ipsclass->input['nav'] == 'lt' )
		{
			$this->nav = array( $this->ipsclass->lang['nav_lt'] );
		}
		else if ( $this->ipsclass->input['nav'] == 'at' )
		{
			$this->nav = array( $this->ipsclass->lang['nav_at'] );
		}
		else if ( $this->ipsclass->input['nav'] == 'au' )
		{
			if( $this->ipsclass->input['result_type'] == 'topics' )
			{
				$this->nav = array( $this->ipsclass->lang['nav_aut'] );
			}
			else
			{
				$this->nav = array( $this->ipsclass->lang['nav_au'] );
			}
		}		
		else
		{
			$this->nav = array( "<a href='{$this->base_url}act=Search'>{$this->ipsclass->lang['search_form']}</a>", $this->ipsclass->lang['search_title'] );
		}
		
		//-----------------------------------------
		// Active topics fing?
		//-----------------------------------------
		
		if ( isset($this->ipsclass->input['lastdate']) )
		{
			$this->output = preg_replace( "#(value=[\"']".intval($this->ipsclass->input['lastdate'])."[\"'])#i", "\\1 selected='selected'", $this->output );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Start the page functions
	/*-------------------------------------------------------------------------*/
	
	function start_page($amount, $is_post = 0)
	{
		$url_words = $this->convert_highlite_words( (isset($this->ipsclass->input['highlite']) AND $this->ipsclass->input['highlite']) ? $this->ipsclass->input['highlite'] : '' );
		$extra     = $this->more_results ? str_replace( '%num', $this->resultlimit, $this->ipsclass->lang['too_many_children_for_santa'] ) : "";
		
		$this->links = $this->ipsclass->build_pagelinks( array( 'TOTAL_POSS'  => $amount,
													 'PER_PAGE'    => 25,
													 'CUR_ST_VAL'  => $this->first,
													 'L_SINGLE'    => "",
													 'L_MULTI'     => $this->ipsclass->lang['search_pages'],
													 'BASE_URL'    => $this->base_url."act=Search&amp;nav=".$this->ipsclass->input['nav']."&amp;CODE=show&amp;searchid=".$this->unique_id."&amp;search_in=".$this->search_in."&amp;result_type=".$this->result_type."&amp;hl=".$url_words,
											)	   );
									  
		if ($is_post == 0)
		{
			return $this->ipsclass->compiled_templates['skin_search']->start( array( 'SHOW_PAGES' => $this->links ), $extra  );
		}
		else
		{
			return $this->ipsclass->compiled_templates['skin_search']->start_as_post( array( 'SHOW_PAGES' => $this->links ), $extra );
		}
	}
    
    /*-------------------------------------------------------------------------*/
    // Parse search result entry
    /*-------------------------------------------------------------------------*/
    
	function parse_entry($topic, $view_as_post=0)
	{
		$this->ipsclass->forum_read[ $topic['forum_id'] ] = isset( $this->ipsclass->forum_read[ $topic['forum_id'] ] ) ? $this->ipsclass->forum_read[ $topic['forum_id'] ] : 0;
		$this->ipsclass->forum_read[0]                    = isset( $this->ipsclass->forum_read[0] ) ? $this->ipsclass->forum_read[0] : 0;
	
		$this->ipsclass->input['last_visit'] = ( $this->ipsclass->forum_read[ $topic['forum_id'] ] > $this->ipsclass->input['last_visit'] )
        						       		 ?   $this->ipsclass->forum_read[ $topic['forum_id'] ] : $this->ipsclass->input['last_visit'];
		
		
		
		if ( isset($this->ipsclass->member['members_markers']['board']) AND $this->ipsclass->member['members_markers']['board'] > $this->ipsclass->input['last_visit'] )
		{
			$this->ipsclass->input['last_visit'] = $this->ipsclass->member['members_markers']['board'];
		}
		
		//-----------------------------------------
		// Over ride with 'master' cookie?
		//-----------------------------------------
		
		if ( $this->ipsclass->forum_read[ $topic['forum_id'] ] AND $this->ipsclass->forum_read[0] > $this->ipsclass->forum_read[ $topic['forum_id'] ] )
		{
			$this->ipsclass->forum_read[ $topic['forum_id'] ] = $this->ipsclass->forum_read[0];
		}
		
		//-----------------------------------------
		// Disable DB tracking...
		// ^^ We'll disable this disabling for now
		// as it seems to affect today's active tpx
		//-----------------------------------------
		
		//$tmp = $this->ipsclass->vars['db_topic_read_cutoff'];
		//$this->ipsclass->vars['db_topic_read_cutoff'] = 0;
		
		//-----------------------------------------
		// Stop search from marking forum as read
		//-----------------------------------------
		
		$topic['db_read'] = isset($topic['db_read']) ? $topic['db_read'] : 0;
		
		if ( is_array( $this->read_array ) )
		{
			if ( array_key_exists( $topic['tid'], $this->read_array ) )
			{
				if ( ! $topic['db_read'] OR $this->read_array[$topic['tid']] > $topic['db_read'] )
				{
					$topic['db_read'] = $this->read_array[$topic['tid']];
				}
			}
		}
	
		$this->forums->new_posts = 1;
		
		$topic = $this->forums->parse_data( $topic, 1 );
		
		if ($topic['pinned'] == 1)
		{
			$topic['prefix']     = $this->ipsclass->vars['pre_pinned'];
			$topic['topic_icon'] = "<{B_PIN}>";
		}
		
		$topic['class1']     = "row2";
		$topic['class2']     = "row1";
		$topic['classposts'] = "row2";
		
		if ( isset($this->ipsclass->member['is_mod']) AND $this->ipsclass->member['is_mod'] )
		{
			if ( ! $topic['approved'] )
			{
				$topic['class1']     = 'row4shaded';
				$topic['class2']     = 'row2shaded';
				$topic['classposts'] = 'row4shaded';
			}
			else if ( isset($topic['queued']) AND $topic['queued'] )
			{
				$topic['classposts'] = 'row4shaded';
			}
		}
		
		//-----------------------------------------
		// Remove potential [attachmentid= tag in title
		//-----------------------------------------
		
		$topic['title'] = str_replace( '[attachmentid=', '&#91;attachmentid=', $topic['title'] );
		
		//-----------------------------------------
		// Extra processing for posts..
		//-----------------------------------------
		
		if ($view_as_post == 1)
		{
			if ( $this->ipsclass->vars['search_post_cut'] )
			{
				//$topic['post'] = substr( strip_tags($topic['post']), 0, $this->ipsclass->vars['search_post_cut']) . '...';
				$topic['post'] = $this->ipsclass->txt_mbsubstr( strip_tags($topic['post']), 0, $this->ipsclass->vars['search_post_cut'] ).'...';
				$topic['post'] = str_replace( "\n", "<br />", $topic['post'] );
			}
			
			if ($topic['author_id'])
			{
				$topic['author_name'] = $topic['members_display_name'] ? $topic['members_display_name'] : $topic['author_name'];
				$topic['author_name'] = "<b><a href='{$this->base_url}showuser={$topic['author_id']}'>{$topic['author_name']}</a></b>";
			}
			
			//-----------------------------------------
			// Highlighting?
			//-----------------------------------------
			
			if ( $topic['keywords']) 
			{
				$topic['post'] = $this->ipsclass->content_search_highlight( $topic['post'], $topic['keywords'] );
			}
		}
		
		if ( ! $this->ipsclass->member['view_img'] )
		{
			//-----------------------------------------
			// unconvert smilies first, or it looks a bit crap.
			//-----------------------------------------
			
			$topic['post'] = preg_replace( "#<!--emo&(.+?)-->.+?<!--endemo-->#", "\\1" , $topic['post'] );
			
			$topic['post'] = preg_replace( "/<img src=[\"'](.+?)[\"'].+?".">/", "(IMG:<a href='\\1' target='_blank'>\\1</a>) ", $topic['post'] );
		}
		
		$topic['forum_full_name'] = $this->ipsclass->forums->forum_by_id[ $topic['forum_id'] ]['name'];
		
		if ( $this->ipsclass->txt_mb_strlen($topic['forum_full_name']) > 50 )
		{
			$topic['forum_name'] = $this->ipsclass->txt_truncate( $topic['forum_full_name'], 47 );
		}
		else
		{
			$topic['forum_name'] = $topic['forum_full_name'];
		}
		
		if( $this->ipsclass->forums->forum_by_id[ $topic['forum_id'] ]['forum_allow_rating'] )
		{
			if ( $topic['topic_rating_total'] )
			{
				$topic['_rate_int'] = round( $topic['topic_rating_total'] / $topic['topic_rating_hits'] );
			}
			
			//-----------------------------------------
			// Show image?
			//-----------------------------------------
			
			if ( ( $topic['topic_rating_hits'] >= $this->ipsclass->vars['topic_rating_needed'] ) AND ( $topic['_rate_int'] ) )
			{
				$topic['_rate_img']  = $this->ipsclass->compiled_templates['skin_search']->topic_rating_image( $topic['_rate_int'] );
			}
		}		
		
		return $topic;
	}
        
    /*-------------------------------------------------------------------------*/
    // Filter keywords
    /*-------------------------------------------------------------------------*/
        
    function filter_keywords($words="", $name=0)
    {
    	// force to lowercase and swop % into a safer version
    	$words = trim( str_replace( "%", "", $words) );

    	// Remove trailing boolean operators

    	$words = preg_replace( "/\s+(and|or)$/" , "" , $words );

    	// Swop wildcard into *SQL percent
    	//$words = str_replace( "*", "%", $words );
    	
    	// Make safe underscores
    	
    	if( $name == 0 )
    	{
    		$words = str_replace( "_", "\\_", $words );
		}

    	$words = str_replace( '|', "&#124;", $words );
    	
    	// Remove crap
    	
    	if ($name == 0)
    	{
    		$words = preg_replace( "/[\|\[\]\{\}\(\)\,:\\\\\/\"']|&quot;/", "", $words );
    	}

    	// Remove common words.. (should be expanded upon in a later release to return 'not searchable word'
    	
    	$words = preg_replace( "/^(?:img|quote|code|html|javascript|a href|color|span|div|border|style)$/", "", $words );

    	return " ".preg_quote($words)." ";
    }
    
    /*-------------------------------------------------------------------------*/
    // Filter keywords
    /*-------------------------------------------------------------------------*/
    
    function filter_ftext_keywords($words="")
    {
		// force to lowercase and swop % into a safer version
    	
    	$words = trim( rawurldecode($words) );
    	$words = str_replace( '|', "&#124;", $words );
    	
    	// Remove crap
    	
    	$words = str_replace( "&quot;", '"', $words );
    	$words = str_replace( "&gt;"  , ">", $words );
    	$words = str_replace( "%"     , "" , $words );
    	
    	//-----------------------------------------
    	// If it's a phrase in quotes..
    	//-----------------------------------------
    	
    	if ( preg_match( "#^\"(.+?)\"$#", $words ) )
    	{
    		return $words;
    	}
    	
    	// Remove common words..
    	
    	$words = preg_replace( "/^(?:img|quote|code|html|javascript|a href|color|span|div|border|style)$/", "", $words );
    	
    	// OK, lets break up the keywords
    	
    	// this or that and this not me
    	
    	$words = preg_replace( "/\s+and\s+/i", " ", $words );
    	
    	// this or that this not me
    	
    	$words = preg_replace( "/\s+not\s+/i", " -", $words );
    	
    	// this or that this -me
    	
    	$words = preg_replace( "/\s+or\s+/i", ' ~', $words );
    	
    	// this ~that this -me
    	
    	# Was added as a bug fix but really this causes more problems
    	# than it solves. Complaint was that it should default to AND
    	# matching, not OR matching. Problem is that it doesn't then
    	# give a "true" search as one would expect Google to do.
    	
    	//$words = preg_replace( "/\s+(?!-|~)/", " +", $words );
    	
    	
    	// this ~that +this -me
    	
    	$words = str_replace( "~", "", $words );
    	
    	// this that +this -me
    	
    	return "+".$words;
    }
    
    /*-------------------------------------------------------------------------*/
    // Make the hl words nice and stuff
    /*-------------------------------------------------------------------------*/
    
    function convert_highlite_words($words="")
    {
    	$words = $this->ipsclass->parse_clean_value(trim(urldecode($words)));
    	
    	// Convert booleans to something easy to match next time around
    	
    	$words = preg_replace("/\s+(and|or)(\s+|$)/i", ",\\1,", $words);
    	
    	// Convert spaces to plus signs
    	
    	$words = preg_replace("/\s/", "+", $words);
    	
    	return $words;
    }
        
    /*-------------------------------------------------------------------------*/
    // Get the searchable forums
    /*-------------------------------------------------------------------------*/
        
    function get_searchable_forums()
    {
		$forumids = array();
		
    	//-----------------------------------------
    	// Check for an array
    	//-----------------------------------------
    	
    	if ( isset($_POST['forums']) AND is_array( $_POST['forums'] )  )
    	{
    	
    		if ( in_array( 'all', $_POST['forums'] ) )
    		{
    			//-----------------------------------------
    			// Searching all forums..
    			//-----------------------------------------
    			
    			foreach( $this->ipsclass->forums->forum_by_id as $data )
    			{
    				$forumids[] = $data['id'];
    			}
    		}
    		else
    		{
				//-----------------------------------------
				// Go loopy loo
				//-----------------------------------------
				
				foreach( $_POST['forums'] as $l )
				{
					if ( $this->ipsclass->forums->forum_by_id[ $l ] )
					{
						$forumids[] = intval($l);
					}
				}
				
				//-----------------------------------------
				// Do we have cats? Give 'em to Charles!
				//-----------------------------------------
				
				if ( count( $forumids  ) )
				{
					if ( $this->ipsclass->input['searchsubs'] == 1 )
					{					
						foreach( $forumids as $f )
						{
							$children = $this->ipsclass->forums->forums_get_children( $f );
							
							if ( is_array($children) and count($children) )
							{
								$forumids  = array_merge( $forumids , $children );
							}
						}
					}
				}
				else
				{
					//-----------------------------------------
					// No forums selected / we have available
					//-----------------------------------------
					
					return;
				}
    		}
		}
		else
		{
			//-----------------------------------------
			// Not an array...
			//-----------------------------------------
			
			if ( $this->ipsclass->input['forums'] == 'all' )
			{
				foreach( $this->ipsclass->forums->forum_by_id as $data )
    			{
    				$forumids[] = $data['id'];
    			}
			}
			else
			{
				if ( $this->ipsclass->input['forums'] != "" )
				{
					$l = intval($this->ipsclass->input['forums']);
					
					//-----------------------------------------
					// Single forum
					//-----------------------------------------
					
					if ( $this->ipsclass->forums->forum_by_id[ $l ] )
					{
						$forumids[] = intval($l);
					}
					
					if ( $this->ipsclass->input['searchsubs'] == 1 )
					{
						$children = $this->ipsclass->forums->forums_get_children( $l );
						
						if ( is_array($children) and count($children) )
						{
							$forumids  = array_merge( $forumids , $children );
						}
					}
				}
			}
		}
		
		$final = array();
		
		foreach( $forumids  as $f )
		{
			$f = intval($f);
			
			if ( $this->check_access($this->ipsclass->forums->forum_by_id[ $f ] ) == TRUE )
			{
				$final[] = $f;
			}
		}
		
		//-----------------------------------------
		// Remove blocked forums
		//-----------------------------------------
		
		if ( $this->ipsclass->input['CODE'] == 'getnew' OR $this->ipsclass->input['CODE'] == 'getactive' )
    	{
    		$block_forums = explode( ',', $this->ipsclass->vars['vnp_block_forums'] );
    		
    		if ( is_array( $block_forums ) and count( $block_forums ) )
    		{
    			if ( is_array( $final ) and count( $final ) )
    			{
    				$tmp   = $final;
    				$final = array();
    			
					foreach( $tmp as $id )
					{
						if ( in_array( $id, $block_forums ) )
						{
							continue;
						}
						else
						{
							$final[] = $id;
						}
					}
    			}
    		}
    	}
    	
    	//-----------------------------------------
    	// Return
    	//-----------------------------------------
    	
    	$this->searchable_forums = $final;

    	return implode( "," , $final );
    }
        
    /*-------------------------------------------------------------------------*/
    // Check password...
    /*-------------------------------------------------------------------------*/
    
    function check_access($i)
    {
		$can_read = FALSE;
  
		$i['read_perms'] = isset($i['read_perms']) ? $i['read_perms'] : NULL;
		
    	if ( $this->ipsclass->check_perms( $i['read_perms'] ) == TRUE )
    	{
    		$can_read = TRUE;
    	}
    	else
    	{
    		$can_read = FALSE;
    	}
        
    	$i['password'] = isset($i['password']) ? $i['password'] : NULL;
    	
        if ( $i['password'] != "" and $can_read == TRUE )
		{
			if ( $this->ipsclass->forums->forums_compare_password( $i['id'] ) == TRUE )
			{
				$can_read = TRUE;
			}
			else
			{
				$can_read = FALSE;

				if( $i['password_override'] )
				{
					$bypass_groups = explode( ",", $i['password_override'] );
					
					$my_groups = array( $this->ipsclass->member['mgroup'] );
					
					if( $this->ipsclass->member['mgroup_others'] )
					{
						$my_groups = array_merge( $my_groups, explode( ",", $this->ipsclass->clean_perm_string( $this->ipsclass->member['mgroup_others'] ) ) );
					}

					foreach( $my_groups as $g_id )
					{
						if( in_array( $g_id, $bypass_groups ) )
						{
							$can_read = TRUE;
						}
					}
				}
			}
		}
		
		return $can_read;
	}
        
}

?>