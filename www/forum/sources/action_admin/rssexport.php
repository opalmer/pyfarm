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
|   > $Date: 2007-09-28 10:16:31 -0400 (Fri, 28 Sep 2007) $
|   > $Revision: 1117 $
|   > $Author: bfarber $
+---------------------------------------------------------------------------
|
|   > Components Functions
|   > Module written by Matt Mecham
|   > Date started: 12th April 2005 (13:09)
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class ad_rssexport
{
	# Globals
	var $ipsclass;
	
	var $perm_main  = 'content';
	var $perm_child = 'rssexport';
	
	var $use_sockets = 1;
	
	/*-------------------------------------------------------------------------*/
	// Main handler
	/*-------------------------------------------------------------------------*/
	
	function auto_run() 
	{
		$this->ipsclass->admin->nav[] 		= array( $this->ipsclass->form_code, 'RSS Export Manager' );
		$this->html = $this->ipsclass->acp_load_template('cp_skin_rss');
		
		switch($this->ipsclass->input['code'])
		{
			case 'rssexport_overview':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->rssexport_overview();
				break;
			
			case 'rssexport_add':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->rssexport_form('add');
				break;
				
			case 'rssexport_edit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->rssexport_form('edit');
				break;
				
			case 'rssexport_add_save':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->rssexport_save('add');
				break;
				
			case 'rssexport_edit_save':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->rssexport_save('edit');
				break;
				
			case 'rssexport_recache':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':recache' );
				$this->rssexport_rebuild_cache();
				break;
			
			case 'rssexport_delete':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':remove' );
				$this->rssexport_delete();
				break;
				
			default:
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->rssexport_overview();
				break;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// RSS Export: Rebuild cache
	/*-------------------------------------------------------------------------*/
	
	function rssexport_rebuild_cache( $rss_export_id='', $return=1 )
	{
		//--------------------------------------------
		// INIT
		//--------------------------------------------
		
		if ( ! $rss_export_id )
		{
			$rss_export_id = $this->ipsclass->input['rss_export_id'] == 'all' ? 'all' : intval($this->ipsclass->input['rss_export_id']);
		}
		
		//--------------------------------------------
		// Check
		//--------------------------------------------
		
		if ( ! $rss_export_id )
		{
			$this->ipsclass->main_msg = "No ID was passed, please try again";
			$this->rssexport_overview();
			return;
		}
		
		//--------------------------------------------
		// Require classes
		//--------------------------------------------
		
		require_once( KERNEL_PATH . 'class_rss.php' );
		$class_rss              =  new class_rss();
		$class_rss->ipsclass    =& $this->ipsclass;
		$class_rss->use_sockets =  $this->use_sockets;
		$class_rss->doc_type    =  $this->ipsclass->vars['gb_char_set'];
		
		//--------------------------------------------
		// Reset rss_export cache
		//--------------------------------------------
		
		$this->ipsclass->cache['rss_export'] = array();
		
		//--------------------------------------------
		// Load skin
		//--------------------------------------------
		
		$this->ipsclass->load_skin();	
		
		//--------------------------------------------
		// Go loopy
		//--------------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'rss_export' ) );
		$outer = $this->ipsclass->DB->exec_query();
		
		while( $row = $this->ipsclass->DB->fetch_row( $outer ) )
		{
			//--------------------------------------------
			// Update RSS cache
			//--------------------------------------------
			
			if ( $row['rss_export_enabled'] )
			{
				$this->ipsclass->cache['rss_export'][] = array( 'url'   => $this->ipsclass->vars['board_url'].'/index.php?act=rssout&amp;id='.$row['rss_export_id'],
																'title' => $row['rss_export_title'] );
			}
			
			if ( $rss_export_id == 'all' OR $row['rss_export_id'] == $rss_export_id )
			{
				//--------------------------------------------
				// Build DB query
				//--------------------------------------------
				
				if ( $row['rss_export_include_post'] )
				{
					$this->ipsclass->DB->build_query( array( 'select' => 't.*',
															 'from'   => array( 'topics' => 't' ),
															 'where'  => "t.forum_id IN( ".$row['rss_export_forums']." ) AND t.state != 'link' AND t.approved=1",
															 'order'  => 't.'.$row['rss_export_order'].' '. $row['rss_export_sort'],
															 'limit'  => array( 0, $row['rss_export_count'] ),
															 'add_join' => array( 0 => array( 'select' => 'p.post',
																							  'from'   => array( 'posts' => 'p' ),
																							  'where'  => 't.topic_firstpost=p.pid',
																							  'type'   => 'left'
																				)           )
													)      );
				}
				else
				{
					$this->ipsclass->DB->build_query( array( 'select' => '*',
															 'from'   => 'topics',
															 'where'  => "forum_id IN( ".$row['rss_export_forums']." ) AND state != 'link' AND approved=1",
															 'order'  => $row['rss_export_order'].' '. $row['rss_export_sort'],
															 'limit'  => array( 0, $row['rss_export_count'] )
													)      );
				}
				
				//--------------------------------------------
				// Exec Query
				//--------------------------------------------
				
				$inner = $this->ipsclass->DB->exec_query();
				
				//--------------------------------------------
				// Create Channel
				//--------------------------------------------
				
				$channel_id = $class_rss->create_add_channel( array( 'title'       => $row['rss_export_title'],
																	 'description' => $row['rss_export_desc'],
																	 'link'        => $this->ipsclass->vars['board_url'].'/index.php',
																	 'pubDate'     => $class_rss->rss_unix_to_rfc( time() ),
																	 'ttl'         => $row['rss_export_cache_time']
															)      );

				if( $row['rss_export_image'] )
				{
					$class_rss->create_add_image( $channel_id, array( 'title'     	=> $row['rss_export_title'],
																		'url'		=> $row['rss_export_image'],
																		'link'		=> $this->ipsclass->vars['board_url'].'/index.php' ) );
				}
															
				//--------------------------------------------
				// Loop through topics and display
				//--------------------------------------------
				
				while( $topic = $this->ipsclass->DB->fetch_row( $inner ) )
				{
					//--------------------------------------------
					// Parse...
					//--------------------------------------------
					
					$topic['post'] = preg_replace( "#\[attachment=(\d+?)\:(?:[^\]]+?)\]#is", "<a href='".$this->ipsclass->vars['board_url']."/index.php?act=attach&type=post&id=\\1'>".$this->ipsclass->vars['board_url']."/index.php?act=attach&type=post&id=\\1</a>", $topic['post'] );
			
					//-----------------------------------------
					// Get the macros and replace them
					//-----------------------------------------
					
					if ( is_array( $this->ipsclass->skin['_macros'] ) )
					{
						foreach( $this->ipsclass->skin['_macros'] as $rowm )
						{
							if ( isset($row['macro_value']) AND $row['macro_value'] != "" )
							{
								$topic['post'] = str_replace( "<{".$rowm['macro_value']."}>", $rowm['macro_replace'], $topic['post'] );
							}
						}
					}
					
					//-----------------------------------------
					// Fix up relative URLS
					//-----------------------------------------
					
					$topic['post'] = preg_replace( "#([^/])style_images/(<\#IMG_DIR\#>)#is", "\\1".$this->ipsclass->vars['board_url']."/style_images/\\2" , $topic['post'] );
					$topic['post'] = preg_replace( "#([\"'])style_emoticons/#is"			, "\\1".$this->ipsclass->vars['board_url']."/style_emoticons/", $topic['post'] );
					
					//-----------------------------------------
					// Convert smilies and emos
					//-----------------------------------------
					
					$topic['post'] = str_replace( "<#IMG_DIR#>", $this->ipsclass->skin['_imagedir'], $topic['post'] );
					$topic['post'] = str_replace( "<#EMO_DIR#>", $this->ipsclass->skin['_emodir']  , $topic['post'] );
					
					$topic['last_poster_name'] 	= $topic['last_poster_name'] ? $topic['last_poster_name'] : 'Guest';
					$topic['starter_name']		= $topic['starter_name']	 ? $topic['starter_name']	  : 'Guest';
					
					//-----------------------------------------
					// Add item
					//-----------------------------------------
					
					$class_rss->create_add_item( $channel_id, array( 'title'           	=> $topic['title'],
																	 'link'            	=> $this->ipsclass->vars['board_url'].'/index.php?showtopic='.$topic['tid'],
																	 'description'     	=> $topic['post'],//$topic['description'],
																	// 'content:encoded' => $topic['post'],
																	 //'starter'			=> $topic['starter_name'],
																	 //'poster'			=> $topic['last_poster_name'],
																	 'pubDate'	       	=> $class_rss->rss_unix_to_rfc( $topic['start_date'] ),
																	 //'lastPostDate' 	=> $class_rss->rss_unix_to_rfc( $topic['last_post'] ),
																	 'guid'            	=> $this->ipsclass->vars['board_url'] . '/index.php?showtopic=' . $topic['tid']
											  )                    );
				}
				
				//--------------------------------------------
				// Build document
				//--------------------------------------------
				
				$class_rss->rss_create_document();
				
				//--------------------------------------------
				// Update the cache
				//--------------------------------------------
				
				$this->ipsclass->DB->do_update( 'rss_export', array( 'rss_export_cache_last'    => time(),
																	 'rss_export_cache_content' => $class_rss->rss_document ), 'rss_export_id='.$row['rss_export_id'] );
 			}
		}
		
		//header( "Content-type: text/plain");
		//print $class_rss->rss_document; exit();
		
		//--------------------------------------------
		// Update cache
		//--------------------------------------------
		
		$this->ipsclass->update_cache( array( 'name' => 'rss_export', 'deletefirst' => 1, 'donow' => 1, 'array' => 1 ) );
		
		//--------------------------------------------
		// Return
		//--------------------------------------------
		
		if ( $return )
		{
			$this->ipsclass->main_msg = "RSS Export(s) Re-cached";
			$this->rssexport_overview();
			return;
		}
		else
		{
			return $class_rss->rss_document;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// RSS Export: Delete
	/*-------------------------------------------------------------------------*/
	
	function rssexport_delete()
	{
		//--------------------------------------------
		// INIT
		//--------------------------------------------
		
		$rss_export_id = intval($this->ipsclass->input['rss_export_id']);
		
		//--------------------------------------------
		// Load RSS stream
		//--------------------------------------------
		
		$rssstream = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'rss_export', 'where' => "rss_export_id=$rss_export_id" ) );
		
		if ( ! $rssstream['rss_export_id'] )
		{
			$this->ipsclass->main_msg = "Could not load the RSS stream from the database. It maybe missing.";
			$this->rssexport_overview();
			return;
		}
		
		//--------------------------------------------
		// Remove it
		//--------------------------------------------
		
		$this->ipsclass->DB->build_and_exec_query( array( 'delete' => 'rss_export', 'where' => 'rss_export_id='.$rss_export_id ) );
		
		$this->rssexport_rebuild_cache( $rss_export_id, 0 );
		$this->ipsclass->main_msg = "RSS Export stream removed.";
		$this->rssexport_overview();
	}
	
	/*-------------------------------------------------------------------------*/
	// RSS Export: Save
	/*-------------------------------------------------------------------------*/
	
	function rssexport_save($type='add')
	{
		//--------------------------------------------
		// INIT
		//--------------------------------------------
		
		$rss_export_id           = intval($this->ipsclass->input['rss_export_id']);
		$rss_export_title        = $this->ipsclass->txt_UNhtmlspecialchars( trim( $this->ipsclass->input['rss_export_title'] ) );
		$rss_export_desc         = $this->ipsclass->txt_UNhtmlspecialchars( trim( $this->ipsclass->input['rss_export_desc']  ) );
		$rss_export_image        = $this->ipsclass->txt_UNhtmlspecialchars( trim( $this->ipsclass->input['rss_export_image'] ) );
		$rss_export_forums       = is_array($_POST['rss_export_forums']) ? implode( ",", $_POST['rss_export_forums'] ) : '';
		$rss_export_include_post = intval($this->ipsclass->input['rss_export_include_post']);
		$rss_export_count        = intval($this->ipsclass->input['rss_export_count']);
		$rss_export_cache_time   = intval($this->ipsclass->input['rss_export_cache_time']);
		$rss_export_enabled      = intval($this->ipsclass->input['rss_export_enabled']);
		$rss_export_sort         = trim( $this->ipsclass->input['rss_export_sort'] );
		$rss_export_order        = trim( $this->ipsclass->input['rss_export_order'] );
		
		//--------------------------------------------
		// Checks...
		//--------------------------------------------
		
		if ( $type == 'edit' )
		{
			if ( ! $rss_export_id )
			{
				$this->ipsclass->main_msg = "No ID was passed, please try again";
				$this->rssexport_overview();
				return;
			}
		}
		
		if ( ! $rss_export_title OR ! $rss_export_count OR ! $rss_export_forums )
		{
			$this->ipsclass->main_msg = "You must complete the entire form.";
			$this->rssexport_form( $type );
			return;
		}
		
		//--------------------------------------------
		// Save...
		//--------------------------------------------
		
		$array = array( 
						'rss_export_enabled'      => $rss_export_enabled,
						'rss_export_title'        => $rss_export_title,
						'rss_export_desc'		  => $rss_export_desc,
						'rss_export_image'        => $rss_export_image,
						'rss_export_forums'       => $rss_export_forums,
						'rss_export_include_post' => $rss_export_include_post,
						'rss_export_count'        => $rss_export_count,
						'rss_export_cache_time'   => $rss_export_cache_time,
						'rss_export_order'        => $rss_export_order,
						'rss_export_sort'         => $rss_export_sort
					 );
					 
		if ( $type == 'add' )
		{
			$this->ipsclass->DB->do_insert( 'rss_export', $array );
			$rss_export_id = 'all';
			$this->ipsclass->main_msg = 'RSS Export Stream Created';
		}
		else
		{
			
			$this->ipsclass->DB->do_update( 'rss_export', $array, 'rss_export_id='.$rss_export_id );
			$this->ipsclass->main_msg = 'RSS Export Stream Edited';
		}
		
		$this->rssexport_rebuild_cache( $rss_export_id, 0 );
		
		$this->rssexport_overview();
	}
	
	/*-------------------------------------------------------------------------*/
	// RSS Export: Form
	/*-------------------------------------------------------------------------*/
	
	function rssexport_form( $type='add' )
	{
		//-----------------------------------------
		// Init Vars
		//-----------------------------------------
		
		$rss_export_id = isset($this->ipsclass->input['rss_export_id']) ? intval($this->ipsclass->input['rss_export_id']) : 0;
		$dd_sort       = array( 0 => array( 'DESC', 'Descending (9-0)' ), 1 => array( 'ASC', 'Ascending (0-9)' ) );
		$dd_order      = array( 0 => array( 'start_date'        , 'Topic Start Date' ),
								1 => array( 'last_post'         , 'Topic Last Post' ),
								2 => array( 'views'             , 'Topic Views' ),
								3 => array( 'starter_id'        , 'Topic Starter' ),
								4 => array( 'topic_rating_total', 'Topic Rating' ) );
		
		//-----------------------------------------
		// Check (please?)
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			$formcode  = 'rssexport_add_save';
			$title     = "Create New RSS Export Stream";
			$button    = "Create New RSS Export Stream";
			
			$rssstream = array( 'rss_export_id'			=> 0,
								'rss_export_title'		=> '',
								'rss_export_forums'		=> NULL,
								'rss_export_desc'		=> '',
								'rss_export_image'		=> '',
								'rss_export_include_post' => 1,
								'rss_export_enabled'	=> 1,
								'rss_export_count'		=> '',
								'rss_export_cache_time'	=> '',
								'rss_export_sort'		=> '',
								'rss_export_order'		=> '' );
		}
		else
		{
			$rssstream = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'rss_export', 'where' => 'rss_export_id='.$rss_export_id ) );
			
			if ( ! $rssstream['rss_export_id'] )
			{
				$this->ipsclass->main_msg = "No ID was passed, please try again.";
				$this->rssexport_overview();
				return;
			}
			
			$formcode = 'rssexport_edit_save';
			$title    = "Edit RSS Export Stream ".$rssstream['rss_export_title'];
			$button   = "Save Changes";
		}
		
		//-------------------------------
		// Build forums multi-chooser
		//-------------------------------
		
		$this->ipsclass->forums->forums_init();
						
		require_once( ROOT_PATH.'sources/lib/admin_forum_functions.php' );
		$aff               = new admin_forum_functions();
		$aff->ipsclass     =& $this->ipsclass;
		$dropdown          = $aff->ad_forums_forum_list(1);
		$rss_export_forums = ( isset($_POST['rss_export_forums']) AND is_array($_POST['rss_export_forums']) ) ? implode( ",", $_POST['rss_export_forums'] ) : $rssstream['rss_export_forums'];
		
		//-------------------------------
		// Form elements
		//-------------------------------
		
		$form = array();
		
		$form['rss_export_title']        = $this->ipsclass->adskin->form_input(  'rss_export_title'         , $this->ipsclass->txt_htmlspecialchars( ( isset($_POST['rss_export_title']) AND $_POST['rss_export_title'] ) ? $_POST['rss_export_title'] : $rssstream['rss_export_title'] ) );
		$form['rss_export_desc']         = $this->ipsclass->adskin->form_input(  'rss_export_desc'          , $this->ipsclass->txt_htmlspecialchars( ( isset($_POST['rss_export_desc']) AND $_POST['rss_export_desc'] )  ? $_POST['rss_export_desc']  : $rssstream['rss_export_desc']  ) );
		$form['rss_export_image']        = $this->ipsclass->adskin->form_input(  'rss_export_image'         , $this->ipsclass->txt_htmlspecialchars( ( isset($_POST['rss_export_image']) AND $_POST['rss_export_image'] ) ? $_POST['rss_export_image'] : $rssstream['rss_export_image'] ) );
		$form['rss_export_include_post'] = $this->ipsclass->adskin->form_yes_no( 'rss_export_include_post'  , ( isset($_POST['rss_export_include_post']) AND $_POST['rss_export_include_post'] ) ? $_POST['rss_export_include_post'] : $rssstream['rss_export_include_post'] );
		$form['rss_export_enabled']      = $this->ipsclass->adskin->form_yes_no( 'rss_export_enabled'       , ( isset($_POST['rss_export_enabled']) AND $_POST['rss_export_enabled'] ) ? $_POST['rss_export_enabled'] : $rssstream['rss_export_enabled'] );
		$form['rss_export_count']        = $this->ipsclass->adskin->form_simple_input( 'rss_export_count'   , ( isset($_POST['rss_export_count']) AND $_POST['rss_export_count'] )   ? $_POST['rss_export_count']   : $rssstream['rss_export_count'], 5 );
		$form['rss_export_forums']       = $this->ipsclass->adskin->form_multiselect(  'rss_export_forums[]', $dropdown, explode( ",", $rss_export_forums ), 7 );
		$form['rss_export_cache_time']   = $this->ipsclass->adskin->form_simple_input( 'rss_export_cache_time'   , ( isset($_POST['rss_export_cache_time']) AND $_POST['rss_export_cache_time'] )  ? $_POST['rss_export_cache_time']   : $rssstream['rss_export_cache_time'], 5 );
		$form['rss_export_sort']         = $this->ipsclass->adskin->form_dropdown( 'rss_export_sort' , $dd_sort , ( isset($_POST['rss_export_sort']) AND $_POST['rss_export_sort'] )  ? $_POST['rss_export_sort']  : $rssstream['rss_export_sort'] );
		$form['rss_export_order']        = $this->ipsclass->adskin->form_dropdown( 'rss_export_order', $dd_order, ( isset($_POST['rss_export_order']) AND $_POST['rss_export_order'] ) ? $_POST['rss_export_order'] : $rssstream['rss_export_order'] );
		
		$this->ipsclass->html .= $this->html->rss_export_form( $form, $title, $formcode, $button, $rssstream );
		
		$this->ipsclass->admin->page_title  = "RSS Export Manager";
		$this->ipsclass->admin->page_detail = "This section will allow you to manage your RSS export feeds.";
		
		$this->ipsclass->admin->nav[]       = array( '', "Add/Edit RSS Export Stream" );
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// List current RSS exports
	/*-------------------------------------------------------------------------*/
	
	function rssexport_overview()
	{
		//-------------------------------
		// INIT
		//-------------------------------
		
		$content = "";
		$rows    = array();
		
		$st		 = intval($this->ipsclass->input['st']) > 0 ? intval($this->ipsclass->input['st']) : 0;
		
		//-------------------------------
		// Get feed count
		//-------------------------------
		
		$num = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'count(*) as row_count', 'from' => 'rss_export' ) );
		
		$page_links = $this->ipsclass->adskin->build_pagelinks( array( 'TOTAL_POSS'  => $num['row_count'],
											   'PER_PAGE'    => 25,
											   'CUR_ST_VAL'  => $st,
											   'L_SINGLE'    => "",
											   'L_MULTI'     => "Pages: ",
											   'BASE_URL'    => $this->ipsclass->base_url."&{$this->ipsclass->form_code}",
											 )
									  );		

		//-------------------------------
		// Get feeds
		//-------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'rss_export', 'order' => 'rss_export_id ASC' ) );
		$this->ipsclass->DB->simple_exec();
		
		while( $r = $this->ipsclass->DB->fetch_row() )
		{
			//-------------------------------
			// (Alex) Cross
			//-------------------------------
			
			$r['_enabled_img'] = $r['rss_export_enabled'] ? 'aff_tick.png' : 'aff_cross.png';
			
			$content .= $this->html->rss_export_overview_row($r);
		}
		
		$this->ipsclass->html .= $this->html->rss_export_overview( $content, $page_links );
		
		$this->ipsclass->admin->page_title  = "RSS Export Manager";
		$this->ipsclass->admin->page_detail = "This section will allow you to manage your RSS export feeds.";
		$this->ipsclass->admin->output();
	}
	
	
	
	

}


?>