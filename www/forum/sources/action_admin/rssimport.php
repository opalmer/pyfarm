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
|   > $Date: 2007-09-11 12:37:52 -0400 (Tue, 11 Sep 2007) $
|   > $Revision: 1102 $
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

class ad_rssimport
{
	# Globals
	var $ipsclass;
	var $class_rss;
	
	var $perm_main  	= 'content';
	var $perm_child 	= 'rssimport';
	
	var $use_sockets 	= 1;
	var $func_mod;
	var $classes_loaded = 0;
	
	var $import_count   = 0;
	
	var $validate_msg	= array();
	
	/*-------------------------------------------------------------------------*/
	// Main handler
	/*-------------------------------------------------------------------------*/
	
	function auto_run() 
	{
		$this->html = $this->ipsclass->acp_load_template('cp_skin_rss');
		$this->ipsclass->admin->nav[] 		= array( $this->ipsclass->form_code, 'RSS Import Manager' );
		
		//--------------------------------------------
		// Load classes
		//--------------------------------------------
		
		require_once( ROOT_PATH.'sources/lib/func_mod.php' );
		$this->func_mod           =  new func_mod();
		$this->func_mod->ipsclass =& $this->ipsclass;
		
		//--------------------------------------------
		// Sup?
		//--------------------------------------------
		
		switch($this->ipsclass->input['code'])
		{
			case 'rssimport_overview':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->rssimport_overview();
				break;
				
			case 'rssimport_validate':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->rssimport_validate( 1 );
				break;				
			
			case 'rssimport_add':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->rssimport_form('add');
				break;
				
			case 'rssimport_edit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->rssimport_form('edit');
				break;
				
			case 'rssimport_add_save':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->rssimport_save('add');
				break;
				
			case 'rssimport_edit_save':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->rssimport_save('edit');
				break;
				
			case 'rssimport_recache':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':recache' );
				$this->rssimport_rebuild_cache();
				break;
				
			case 'rssimport_remove':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':remove' );
				$this->rssimport_remove_dialogue();
				break;
				
			case 'rssimport_remove_complete':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':remove' );
				$this->rssimport_remove_complete( 1 );
				break;
				
			case 'rssimport_delete':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':remove' );
				$this->rssimport_delete();
				break;
				
			default:
				$this->rssimport_overview();
				break;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// RSS Import: Remove import stream
	/*-------------------------------------------------------------------------*/
	
	function rssimport_delete()
	{
		//--------------------------------------------
		// INIT
		//--------------------------------------------
		
		$rss_import_id = intval($this->ipsclass->input['rss_import_id']);

		//--------------------------------------------
		// Load RSS stream
		//--------------------------------------------
		
		$rssstream = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'rss_import', 'where' => "rss_import_id=$rss_import_id" ) );
		
		if ( ! $rssstream['rss_import_id'] )
		{
			$this->ipsclass->main_msg = "Could not load the RSS stream from the database. It maybe missing.";
			$this->rssimport_overview();
			return;
		}
		
		//--------------------------------------------
		// Remove it
		//--------------------------------------------
		
		$this->ipsclass->DB->build_and_exec_query( array( 'delete' => 'rss_import', 'where' => 'rss_import_id='.$rss_import_id ) );
		
		$this->ipsclass->main_msg = "RSS Import stream removed.";
		$this->rssimport_overview();
	}
	
	/*-------------------------------------------------------------------------*/
	// RSS Import: Remove articles, done
	/*-------------------------------------------------------------------------*/
	
	function rssimport_remove_complete( $return=0, $rss_import_id=0 )
	{
		//--------------------------------------------
		// INIT
		//--------------------------------------------
		
		$rss_import_id = $rss_import_id ? $rss_import_id : intval($this->ipsclass->input['rss_import_id']);
		$remove_count  = intval($this->ipsclass->input['remove_count']) ? intval($this->ipsclass->input['remove_count']) : 500;
		$remove_tids   = array();
		
		//--------------------------------------------
		// Load RSS stream
		//--------------------------------------------
		
		$rssstream = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'rss_import', 'where' => "rss_import_id=$rss_import_id" ) );
		
		if ( ! $rssstream['rss_import_id'] )
		{
			$this->ipsclass->main_msg = "Could not load the RSS stream from the database. It maybe missing.";
			$this->rssimport_overview();
			return;
		}
		
		//--------------------------------------------
		// Get tids
		//--------------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select' => 'rss_imported_tid',
												 'from'   => 'rss_imported',
												 'where'  => 'rss_imported_impid='.$rss_import_id,
												 'order'  => 'rss_imported_tid DESC',
												 'limit'  => array( 0, $remove_count ) ) );
												 
		$this->ipsclass->DB->exec_query();
		
		while( $tee = $this->ipsclass->DB->fetch_row() )
		{
			$remove_tids[ $tee['rss_imported_tid'] ] = $tee['rss_imported_tid'];
		}
		
		//--------------------------------------------
		// Check..
		//--------------------------------------------
		
		if ( ! count( $remove_tids ) )
		{
			if ( $return )
			{
				$this->ipsclass->main_msg = "Could not locate any topics to delete";
				$this->rssimport_overview();
				return;
			}
			else
			{
				return;
			}
		}
		
		//--------------------------------------------
		// Remove 'em
		//--------------------------------------------
		
		$this->func_mod->forum['id'] = $rssstream['rss_import_forum_id'];
		$this->func_mod->topic_delete( $remove_tids );
		
		//--------------------------------------------
		// Remove from DB
		//--------------------------------------------
		
		$this->ipsclass->DB->build_and_exec_query( array( 'delete' => 'rss_imported', 'where' => 'rss_imported_tid IN('.implode(',',$remove_tids).')' ) );
		
		$this->ipsclass->main_msg = intval(count($remove_tids))." topics removed.";
		$this->rssimport_overview();
	}
	
	/*-------------------------------------------------------------------------*/
	// RSS Import: Remove articles, dialogue
	/*-------------------------------------------------------------------------*/
	
	function rssimport_remove_dialogue()
	{
		//--------------------------------------------
		// INIT
		//--------------------------------------------
		
		$rss_import_id = intval($this->ipsclass->input['rss_import_id']);
		
		//--------------------------------------------
		// Checks...
		//--------------------------------------------
		
		if ( ! $rss_import_id )
		{
			$this->ipsclass->main_msg = "No ID was passed, please try again";
			$this->rssimport_overview();
			return;
		}
		
		//--------------------------------------------
		// Load RSS stream
		//--------------------------------------------
		
		$rssstream = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*', 'from' => 'rss_import', 'where' => "rss_import_id=$rss_import_id" ) );
		
		if ( ! $rssstream['rss_import_id'] )
		{
			$this->ipsclass->main_msg = "Could not load the RSS stream from the database. It maybe missing.";
			$this->rssimport_overview();
			return;
		}
		
		//--------------------------------------------
		// Count number of topics...
		//--------------------------------------------
		
		$article_count = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'COUNT(*) as cnt', 'from' => 'rss_imported', 'where' => 'rss_imported_impid='.$rss_import_id ) );
		
		//--------------------------------------------
		// Got any?
		//--------------------------------------------
		
		if ( $article_count['cnt'] < 1 )
		{
			$this->ipsclass->main_msg = "The RSS Import Stream '{$rssstream['rss_import_title']}' has no imported articles to remove.";
			$this->rssimport_overview();
			return;
		}
		
		//--------------------------------------------
		// We must have here...
		//--------------------------------------------
		
		$this->ipsclass->html .= $this->html->rss_import_remove_articles_form( $rssstream, intval($article_count['cnt']) );
		
		$this->ipsclass->admin->page_title  = "RSS Import Manager";
		$this->ipsclass->admin->page_detail = "This section will allow you to remove already posted articles.";
		
		$this->ipsclass->admin->nav[]       = array( '', "Remove RSS Articles" );
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// RSS Import: Rebuild cache
	/*-------------------------------------------------------------------------*/
	
	function rssimport_rebuild_cache( $rss_import_id='', $return=1, $id_is_array=0 )
	{
		//--------------------------------------------
		// INIT
		//--------------------------------------------
		
		$errors             = array();
		$affected_forum_ids = array();
		$affected_members   = array();
		$rss_error         	= array();
		$rss_import_ids		= array();
		$items_imported     = 0;
		
		if ( ! $rss_import_id )
		{
			$rss_import_id = $this->ipsclass->input['rss_import_id'] == 'all' ? 'all' : intval($this->ipsclass->input['rss_import_id']);
		}

		//--------------------------------------------
		// Check
		//--------------------------------------------
		
		if ( ! $rss_import_id )
		{
			$this->ipsclass->main_msg = "No ID was passed, please try again";
			$this->rssimport_overview();
			return;
		}
		
		if( $id_is_array == 1 )
		{
			$rss_import_ids = explode( ",", $rss_import_id );
		}
		
		if ( ! $this->classes_loaded )
		{
			//--------------------------------------------
			// Require classes
			//--------------------------------------------
			
			if ( ! is_object( $this->class_rss ) )
			{
				require_once( KERNEL_PATH . 'class_rss.php' );
				$this->class_rss               =  new class_rss();
				$this->class_rss->ipsclass     =& $this->ipsclass;
				$this->class_rss->use_sockets  =  $this->use_sockets;
				$this->class_rss->rss_max_show =  100;
			}
			
			//-----------------------------------------
			// Force RTE editor
			//-----------------------------------------
			
			require_once( ROOT_PATH."sources/classes/editor/class_editor.php" );
			require_once( ROOT_PATH."sources/classes/editor/class_editor_rte.php" );
			$this->editor           =  new class_editor_module();
			$this->editor->ipsclass =& $this->ipsclass;
		 
			//-----------------------------------------
			// Load and config POST class
			//-----------------------------------------
			
			require_once( ROOT_PATH."sources/classes/post/class_post.php" );
			$this->post           =  new class_post();
			$this->post->ipsclass =& $this->ipsclass;
			
			//-----------------------------------------
			// Load and config the email class
			// - only here not to break post class
			//-----------------------------------------
			
			require_once( ROOT_PATH."sources/classes/class_email.php" );
			$this->email = new emailer( ROOT_PATH );
			$this->email->ipsclass =& $this->ipsclass;
			$this->email->email_init();
			
			$this->post->email =& $this->email;
			
			//-----------------------------------------
			// Load and config the post parser
			//-----------------------------------------
			
			require_once( ROOT_PATH."sources/handlers/han_parse_bbcode.php" );
			$this->parser                      =  new parse_bbcode();
			$this->parser->ipsclass            =& $this->ipsclass;
			$this->parser->allow_update_caches = 0;
			
			//--------------------------------------------
			// Not loaded the func?
			//--------------------------------------------
			
			if ( ! $this->func_mod )
			{
				require_once( ROOT_PATH.'sources/lib/func_mod.php' );
				$this->func_mod           =  new func_mod();
				$this->func_mod->ipsclass =& $this->ipsclass;
			}
			
			$this->classes_loaded = 1;
		}
		
		//--------------------------------------------
		// Init forums if not already done so
		//--------------------------------------------
		
		if ( ! is_array( $this->ipsclass->forums->forum_by_id ) OR !count( $this->ipsclass->forums->forum_by_id ) )
		{
			$this->ipsclass->forums->forums_init();
		}
		
		//--------------------------------------------
		// Go loopy
		//--------------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'rss_import' ) );
		$outer = $this->ipsclass->DB->exec_query();
		
		while( $row = $this->ipsclass->DB->fetch_row( $outer ) )
		{
			if ( $rss_import_id == 'all' OR $row['rss_import_id'] == $rss_import_id OR ( $id_is_array == 1 AND in_array( $row['rss_import_id'], $rss_import_ids ) ) )
			{
				// Skip non-existent forums - bad stuff happens
				
				if ( !array_key_exists( $row['rss_import_forum_id'], $this->ipsclass->cache['forum_cache'] ) )
				{
					continue;
				}
				
				//-----------------------------------------
				// Allowing badwords?
				//-----------------------------------------
				
				$this->parser->bypass_badwords = $row['rss_import_allow_html'];
				
				//--------------------------------------------
				// Set this import's doctype
				//--------------------------------------------
				
				$this->class_rss->doc_type 		= $this->ipsclass->vars['gb_char_set'];
				$this->class_rss->feed_charset 	= $row['rss_import_charset'];
				
				if( strtolower($row['rss_import_charset']) != $this->ipsclass->vars['gb_char_set'] )
				{
					$this->class_rss->convert_charset = 1;
				}
				else
				{
					$this->class_rss->convert_charset = 0;
				}
				
				//--------------------------------------------
				// Set this import's authentication
				//--------------------------------------------
				
				$this->class_rss->auth_req 		= $row['rss_import_auth'];
				$this->class_rss->auth_user 	= $row['rss_import_auth_user'];
				$this->class_rss->auth_pass 	= $row['rss_import_auth_pass'];
				
				//--------------------------------------------
				// Clear RSS object's error cache first
				//--------------------------------------------
				
				$this->class_rss->errors 		= array();
				$this->class_rss->rss_items 	= array();
				
				//--------------------------------------------				
				// Reset the rss count as this is a new feed
				//--------------------------------------------
								
				$this->class_rss->rss_count 	= 0;
				$this->class_rss->rss_max_show 	= $row['rss_import_pergo'];
				
				//--------------------------------------------
				// Parse RSS
				//--------------------------------------------
				
				$this->class_rss->rss_parse_feed_from_url( $row['rss_import_url'] );
				
				//--------------------------------------------
				// Got an error?
				//--------------------------------------------
				
				if ( is_array( $this->class_rss->errors ) and count( $this->class_rss->errors ) )
				{
					$rss_error = array_merge( $rss_error,  $this->class_rss->errors );
					continue;
				}
				
				//--------------------------------------------
				// Got anything?
				//--------------------------------------------
				
				if ( ! is_array( $this->class_rss->rss_channels ) or ! count( $this->class_rss->rss_channels ) )
				{
					$rss_error[] = "Could not open {$row['rss_import_url']} to locate channels.";
					continue;
				}
				
				//--------------------------------------------
				// Update last check time
				//--------------------------------------------
				
				$this->ipsclass->DB->do_update( 'rss_import', array( 'rss_import_last_import' => time() ), 'rss_import_id='.$row['rss_import_id'] );
				
				//--------------------------------------------
				// Apparently so: Parse feeds and check for
				// already imported GUIDs
				//--------------------------------------------
				
				$final_items = array();
				$items       = array();
				$check_guids = array();
				$final_guids = array();
				$count       = 0;
				
				if ( ! is_array( $this->class_rss->rss_items ) or ! count( $this->class_rss->rss_items ) )
				{
					$rss_error[] = "{$row['rss_import_url']} has no items to import";
					continue;
				}
				
				foreach ( $this->class_rss->rss_channels as $channel_id => $channel_data )
				{
					if ( is_array( $this->class_rss->rss_items[ $channel_id ] ) and count ($this->class_rss->rss_items[ $channel_id ] ) )
					{
						foreach( $this->class_rss->rss_items[ $channel_id ] as $item_data )
						{
							$item_data['content']  = $item_data['content']   ? $item_data['content']  : $item_data['description'];
							$item_data['guid']     = md5( $item_data['guid'] ? $item_data['guid']     : preg_replace( "#\s|\r|\n#is", "", $item_data['title'].$item_data['link'].$item_data['description'] ) );
							$item_data['unixdate'] = intval($item_data['unixdate'])  ? intval($item_data['unixdate']) : time();
							
							//--------------------------------------------
							// Convert char set?
							//--------------------------------------------
							
							if ( $row['rss_import_charset'] AND ( strtolower($this->ipsclass->vars['gb_char_set']) != strtolower($this->class_rss->doc_type) ) )
							{
								$item_data['title']   = $this->ipsclass->txt_convert_charsets( $item_data['title'], $this->class_rss->doc_type );
								$item_data['content'] = $this->ipsclass->txt_convert_charsets( $item_data['content'], $this->class_rss->doc_type );
							}

							//--------------------------------------------
							// Dates?
							//--------------------------------------------
							
							if ( $item_data['unixdate'] < 1 )
							{
								$item_data['unixdate'] = time();
							}
							else if ( $item_data['unixdate'] > time() )
							{
								$item_data['unixdate'] = time();
							}
							
							
							if ( ! $item_data['title'] OR ! $item_data['content'] )
							{
							 	$rss_error[] = "Skipping '{$item_data['title']}' no title or content";
								continue;
							}
							
							
							$items[ $item_data['guid'] ] = $item_data;
							$check_guids[]               = $item_data['guid'];
						}
					}
				}
				
				//--------------------------------------------
				// Check GUIDs
				//--------------------------------------------
				
				if ( ! count( $check_guids ) )
				{
					$rss_error[] = "No RSS items to import";
					continue;
				}
				
				$this->ipsclass->DB->build_query( array( 'select' => '*', 'from' => 'rss_imported', 'where' => "rss_imported_guid IN ('".implode( "','", $check_guids )."')" ) );
				$this->ipsclass->DB->exec_query();
				
				while ( $guid = $this->ipsclass->DB->fetch_row() )
				{
					$final_guids[ $guid['rss_imported_guid'] ] = $guid['rss_imported_guid'];
				}
				
				//--------------------------------------------
				// Compare GUIDs
				//--------------------------------------------
				
				$item_count = 0;
				
				foreach( $items as $guid => $data )
				{
					if ( in_array( $guid, $final_guids ) )
					{
						continue;
					}
					else
					{
						$item_count++;
						
						# Make sure each item has a unique date
						$final_items[ $data['unixdate'].$item_count ] = $data;
					}
				}
 				
				//--------------------------------------------
				// Sort array
				//--------------------------------------------
				
				krsort( $final_items );
				
				//--------------------------------------------
				// Pick off last X
				//--------------------------------------------
				
				$count           = 1;
				$tmp_final_items = $final_items;
				$final_items     = array();
				
				foreach( $tmp_final_items as $date => $data )
				{
					$final_items[ $date ] = $data;
					
					if ( $count >= $row['rss_import_pergo'] )
					{
						break;
					}
						
					$count++;
				}
				
				//--------------------------------------------
				// Anything left?
				//--------------------------------------------
				
				if ( ! count( $final_items ) )
				{
					continue;
				}
				
				//--------------------------------------------
				// Figure out MID
				//--------------------------------------------
				
				$member = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'id, name, members_display_name, ip_address', 'from' => 'members', 'where' => "id={$row['rss_import_mid']}" ) );
				
				if ( ! $member['id'] )
				{
					continue;
				}
				
				//--------------------------------------------
				// Make 'dem posts
				//--------------------------------------------
				
				$affected_forum_ids[] = $row['rss_import_forum_id'];
				
				foreach( $final_items as $topic_item )
				{
					# Fix &amp;
					$topic_item['title'] = str_replace( '&amp;', '&', $topic_item['title'] );
					$topic_item['title'] = trim( $this->ipsclass->my_br2nl( $topic_item['title'] ) );
					$topic_item['title'] = strip_tags( $topic_item['title'] );
					$topic_item['title'] = $this->ipsclass->parse_clean_value( $topic_item['title'] );
					
					# Fix up &amp;reg;
					$topic_item['title'] = str_replace( '&amp;reg;', '&reg;', $topic_item['title'] );
					
					if( $row['rss_import_topic_pre'] )
					{
						$topic_item['title'] = str_replace( '&nbsp;', ' ', str_replace( '&amp;nbsp;', '&nbsp;', $row['rss_import_topic_pre'] ) ) .' '. $topic_item['title'];
					}

					$topic = array(
									'title'            => $topic_item['title'],
									'description'      => '' ,
									'state'            => $row['rss_import_topic_open'] ? 'open' : 'closed',
									'posts'            => 0,
									'starter_id'       => $member['id'],
									'starter_name'     => $member['members_display_name'],
									'start_date'       => $topic_item['unixdate'],
									'last_poster_id'   => $member['id'],
									'last_poster_name' => $member['members_display_name'],
									'last_post'        => $topic_item['unixdate'],
									'icon_id'          => 0,
									'author_mode'      => 1,
									'poll_state'       => 0,
									'last_vote'        => 0,
									'views'            => 0,
									'forum_id'         => $row['rss_import_forum_id'],
									'approved'         => $row['rss_import_topic_hide'] ? 0 : 1,
									'pinned'           => 0 );
					
					//--------------------------------------------
					// Sort post content: Convert HTML to BBCode
					//--------------------------------------------
					
					$this->parser->parse_smilies = 1;
					$this->parser->parse_html    = 0;
					$this->parser->parse_bbcode  = 1;
					
					//--------------------------------------------
					// Clean up..
					//--------------------------------------------
					
					$topic_item['content'] = preg_replace( "#<br />(\r)?\n#is", "<br />", $topic_item['content'] );
					
					//--------------------------------------------
					// Add in Show link...
					//--------------------------------------------
					
					if ( $row['rss_import_showlink'] AND $topic_item['link'] )
					{
						$the_link = str_replace( '{url}', trim($topic_item['link']), $row['rss_import_showlink'] );

						if ( $row['rss_import_allow_html'] )
						{
							$the_link = "<br /><br />" . $this->parser->pre_display_parse( $this->parser->pre_db_parse( $this->editor->_rte_html_to_bbcode( $this->parser->pre_edit_parse( stripslashes($the_link) ) ) ) );
						}
						else
						{
							$the_link = "\n\n" . $the_link;
						}
						
						$topic_item['content'] .= $the_link;
					}

					//--------------------------------------------
					// Not allowed HTML
					//--------------------------------------------
					
					$this->parser->parse_smilies = 1;
					$this->parser->parse_html    = 0;
					$this->parser->parse_bbcode  = 1;
					#print $this->parser->pre_db_parse( stripslashes($topic_item['content']) );exit;				
					if ( ! $row['rss_import_allow_html'] )
					{
						$post_content = $this->parser->pre_display_parse( $this->parser->pre_db_parse( $this->editor->_rte_html_to_bbcode( $this->parser->pre_edit_parse( stripslashes($topic_item['content']) ) ) ) );
					}
					else
					{
						$post_content = stripslashes($topic_item['content']);
					}

					$post = array(
									'author_id'      => $member['id'],
									'use_sig'        => 1,
									'use_emo'        => 1,
									'ip_address'     => $member['ip_address'],
									'post_date'      => $topic_item['unixdate'],
									'icon_id'        => 0,
									'post'           => $post_content,
									'author_name'    => $member['members_display_name'],
									'topic_id'       => "",
									'queued'         => 0,
									'post_htmlstate' => 0,
								 );
								 
					//-----------------------------------------
					// Insert the topic into the database to get the
					// last inserted value of the auto_increment field
					// follow suit with the post
					//-----------------------------------------
					
					$this->ipsclass->DB->do_insert( 'topics', $topic );
					
					$post['topic_id']  = $this->ipsclass->DB->get_insert_id();
					$topic['tid']      = $post['topic_id'];
					
					//-----------------------------------------
					// Update the post info with the upload array info
					//-----------------------------------------
					
					$post['post_key']  = md5(time());
					$post['new_topic'] = 1;
					
					//-----------------------------------------
					// Add post to DB
					//-----------------------------------------
					
					$this->ipsclass->DB->do_insert( 'posts', $post );
				
					$post['pid'] = $this->ipsclass->DB->get_insert_id();
					
					//-----------------------------------------
					// Update topic with firstpost ID
					//-----------------------------------------
					
					$this->ipsclass->DB->simple_construct( array( 'update' => 'topics',
																  'set'    => "topic_firstpost=".$post['pid'],
																  'where'  => "tid=".$topic['tid']
														 )      );
										 
					$this->ipsclass->DB->simple_exec();
					
					//--------------------------------------------
					// Insert GUID match
					//--------------------------------------------
					
					$this->ipsclass->DB->do_insert( 'rss_imported', array( 'rss_imported_impid' => $row['rss_import_id'],
																		   'rss_imported_guid'  => $topic_item['guid'],
																		   'rss_imported_tid'   => $topic['tid'] ) );
				
					//-----------------------------------------
					// Are we tracking this forum? If so generate some mailies - yay!
					//-----------------------------------------
					
					$this->post->forum = $this->ipsclass->cache['forum_cache'][$row['rss_import_forum_id']];

					$this->post->forum_tracker( $row['rss_import_forum_id'], $topic['tid'], $topic['title'], $this->ipsclass->cache['forum_cache'][ $row['rss_import_forum_id'] ]['name'], $post['post'], $member['id'], $member['members_display_name'] );
					
					if( $topic['approved'] == 0 )
					{
						$this->post->notify_new_topic_approval( $topic['tid'], $topic['title'], $topic['starter_name'], $post['pid'] );
					}
					
					$this->import_count++;
					
					//--------------------------------------------
					// Increment user?
					//--------------------------------------------
					
					if ( $row['rss_import_inc_pcount'] AND $this->ipsclass->cache['forum_cache'][ $row['rss_import_forum_id'] ]['inc_postcount'] )
					{
						if ( ! $affected_members[ $member['id'] ] OR $affected_members[ $member['id'] ] < 0 )
						{
							$affected_members[ $member['id'] ] = 0;
						}
						
						$affected_members[ $member['id'] ]++;
					}
				}
 			}
		}
		
		//--------------------------------------------
		// Update members
		//--------------------------------------------
		
		if ( is_array( $affected_members ) and count( $affected_members ) )
		{
			foreach( $affected_members as $mid => $inc )
			{
				if ( $mid AND $inc )
				{
					$this->ipsclass->DB->simple_update( 'members', 'posts=posts+'.intval($inc), 'id='.intval($mid) );
					$this->ipsclass->DB->simple_exec();
				}
			}
		}
		
		//--------------------------------------------
		// Recount stats
		//--------------------------------------------
		
		if ( is_array( $affected_forum_ids ) and count( $affected_forum_ids ) )
		{
			foreach( $affected_forum_ids as $fid )
			{
				$this->func_mod->forum_recount( $fid );
			}
			
			$this->func_mod->stats_recount();
		}
		
		//--------------------------------------------
		// Return
		//--------------------------------------------
		
		if ( $return )
		{
			$this->ipsclass->main_msg = "RSS Import(s) Re-cached";
			
			if ( count( $rss_error ) )
			{
				$this->ipsclass->main_msg .= "<br />".implode( "<br />", $rss_error );
			}
			
			$this->rssimport_overview();
			return;
		}
		else
		{
			return TRUE;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// RSS Import: Save
	/*-------------------------------------------------------------------------*/
	
	function rssimport_save($type='add')
	{
		//--------------------------------------------
		// Do we want to validate?
		//--------------------------------------------
		
		if( isset($this->ipsclass->input['rssimport_validate']) AND $this->ipsclass->input['rssimport_validate'] )
		{
			$this->rssimport_validate();
			
			if( count($this->validate_msg) )
			{
				$this->ipsclass->main_msg = "<b>Validation Results for <span class='rss-feed-url'>".$this->ipsclass->txt_stripslashes( trim( $_POST['rss_import_url'] ) )."</span></b><br />&nbsp;&middot;".implode( "<br />&nbsp;&middot;", $this->validate_msg );
				$this->rssimport_form( $type );
				return;
			}
		}
				
		//--------------------------------------------
		// INIT
		//--------------------------------------------
		
		$rss_import_id         = intval($this->ipsclass->input['rss_import_id']);
		$rss_import_title      = trim( $this->ipsclass->input['rss_import_title'] );
		$rss_import_url        = $this->ipsclass->txt_stripslashes( trim( $_POST['rss_import_url'] ) );
		$rss_import_mid        = trim( $this->ipsclass->input['rss_import_mid'] );
		$rss_import_showlink   = $this->ipsclass->txt_stripslashes( trim( $_POST['rss_import_showlink'] ) );
		$rss_import_enabled    = intval($this->ipsclass->input['rss_import_enabled']);
		$rss_import_forum_id   = intval($this->ipsclass->input['rss_import_forum_id']);
		$rss_import_pergo      = intval($this->ipsclass->input['rss_import_pergo']);
		$rss_import_time       = intval($this->ipsclass->input['rss_import_time']);
		$rss_import_topic_open = intval($this->ipsclass->input['rss_import_topic_open']);
		$rss_import_topic_hide = intval($this->ipsclass->input['rss_import_topic_hide']);
		$rss_import_inc_pcount = intval($this->ipsclass->input['rss_import_inc_pcount']);
		$rss_import_topic_pre  = $this->ipsclass->input['rss_import_topic_pre'];
		$rss_import_charset    = $this->ipsclass->input['rss_import_charset'];
		$rss_import_allow_html = intval($this->ipsclass->input['rss_import_allow_html']);
		$rss_import_auth	   = intval($this->ipsclass->input['rss_import_auth']);
		$rss_import_auth_user  = trim($this->ipsclass->input['rss_import_auth_user']) ? trim($this->ipsclass->input['rss_import_auth_user']) : 'Not Needed';
		$rss_import_auth_pass  = trim($this->ipsclass->input['rss_import_auth_pass']) ? trim($this->ipsclass->input['rss_import_auth_pass']) : 'Not Needed';
		
		$rss_error             = array();
		
		//--------------------------------------------
		// Checks...
		//--------------------------------------------
		
		if ( $type == 'edit' )
		{
			if ( ! $rss_import_id )
			{
				$this->ipsclass->main_msg = "No ID was passed, please try again";
				$this->rssimport_overview();
				return;
			}
		}
		
		if ( ! $rss_import_title OR ! $rss_import_url OR ! $rss_import_pergo OR ! $rss_import_forum_id OR ! $rss_import_mid )
		{
			$this->ipsclass->main_msg = "You must complete the entire form.";
			$this->rssimport_form( $type );
			return;
		}
		
		//--------------------------------------------
		// Load classes
		//--------------------------------------------
		
		require_once( KERNEL_PATH . 'class_rss.php' );
		$this->class_rss               =  new class_rss();
		$this->class_rss->ipsclass     =& $this->ipsclass;
		$this->class_rss->use_sockets  =  $this->use_sockets;
		$this->class_rss->rss_max_show =  $rss_import_pergo;
		
		//--------------------------------------------
		// Set this import's doctype
		//--------------------------------------------
		
		$supported_encodings = array( 'utf-8', 'iso-8859-1', 'us-ascii' );
		
		if( in_array( strtolower($this->ipsclass->vars['gb_char_set']), $supported_encodings ) )
		{
			$this->class_rss->doc_type = $this->ipsclass->vars['gb_char_set'];
		}
		else
		{
			$this->class_rss->doc_type = 'UTF-8';
		}
		
		if( strtolower($rss_import_charset) != $this->class_rss->doc_type )
		{
			$this->class_rss->convert_charset = 1;
			$this->class_rss->feed_charset = $rss_import_charset;
			$this->class_rss->destination_charset = $this->class_rss->doc_type;
		}
		else
		{
			$this->class_rss->convert_charset = 0;
		}
		
		//--------------------------------------------
		// Set this import's authentication
		//--------------------------------------------
						
		$this->class_rss->auth_req = $rss_import_auth;
		$this->class_rss->auth_user = $rss_import_auth_user;
		$this->class_rss->auth_pass = $rss_import_auth_pass;
				
		//--------------------------------------------
		// Test URL
		//--------------------------------------------
		
		$this->class_rss->rss_parse_feed_from_url( $rss_import_url );
		
		//--------------------------------------------
		// Got an error?
		//--------------------------------------------
		
		if ( is_array( $this->class_rss->errors ) and count( $this->class_rss->errors ) )
		{
			$rss_error = array_merge( $rss_error,  $this->class_rss->errors );
		}
		
		//--------------------------------------------
		// Got anything?
		//--------------------------------------------
		
		if ( ! is_array( $this->class_rss->rss_channels ) or ! count( $this->class_rss->rss_channels ) )
		{
			$rss_error[] = "Could not open $rss_import_url to locate channels.";
		}
		
		if ( is_array( $rss_error ) AND count( $rss_error ) )
		{
			$this->ipsclass->main_msg = implode( "<br />", $rss_error );
			$this->rssimport_form( $type );
			return;
		}

		//--------------------------------------------
		// Figure out MID
		//--------------------------------------------
		
		$member = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'id, name', 'from' => 'members', 'where' => "members_l_display_name='" . strtolower($rss_import_mid) . "'" ) );
		
		if ( !isset($member['id']) OR !$member['id'] )
		{
			$this->ipsclass->main_msg = "We could not find a member called '{$rss_import_mid}'";
			$this->rssimport_form( $type );
			return;
		}
		else
		{
			$rss_import_mid = $member['id'];
		}
		
		//--------------------------------------------
		// Check to make sure forum ID is valid
		//--------------------------------------------
		
		$this->ipsclass->forums->forums_init();
		
		if ( !isset($this->ipsclass->forums->forum_by_id[ $rss_import_forum_id ]) OR !$this->ipsclass->forums->forum_by_id[ $rss_import_forum_id ] )
		{
			$this->ipsclass->main_msg = "The selected forum to import into doesn't exist";
			$this->rssimport_form( $type );
			return;
		}
		
		if ( $this->ipsclass->forums->forum_by_id[ $rss_import_forum_id ]['sub_can_post'] != 1 OR $this->ipsclass->forums->forum_by_id[ $rss_import_forum_id ]['redirect_on'] == 1 )
		{
			$this->ipsclass->main_msg = "The selected forum to import into isn't capable of receiving topics.";
			$this->rssimport_form( $type );
			return;
		}
		
		
		//--------------------------------------------
		// Save...
		//--------------------------------------------
		
		$array = array( 
						'rss_import_title'      => $rss_import_title,
						'rss_import_url'        => $rss_import_url,
						'rss_import_mid'        => $rss_import_mid,
						'rss_import_showlink'   => $rss_import_showlink,
						'rss_import_enabled'    => $rss_import_enabled,
						'rss_import_forum_id'   => $rss_import_forum_id,
						'rss_import_pergo'      => $rss_import_pergo,
						'rss_import_time'       => $rss_import_time < 30 ? 30 : $rss_import_time,
						'rss_import_topic_open' => $rss_import_topic_open,
						'rss_import_topic_hide' => $rss_import_topic_hide,
						'rss_import_inc_pcount' => $rss_import_inc_pcount,
						'rss_import_topic_pre'  => $rss_import_topic_pre,
						'rss_import_charset'    => $rss_import_charset,
						'rss_import_allow_html'	=> $rss_import_allow_html,
						'rss_import_auth'		=> $rss_import_auth,
						'rss_import_auth_user'  => $rss_import_auth_user,
						'rss_import_auth_pass'  => $rss_import_auth_pass,
					 );
					 
		if ( $type == 'add' )
		{
			$this->ipsclass->DB->do_insert( 'rss_import', $array );
			$this->ipsclass->main_msg = 'RSS Import Stream Created';
			$rss_import_id = $this->ipsclass->DB->get_insert_id();
		}
		else
		{
			$this->ipsclass->DB->do_update( 'rss_import', $array, 'rss_import_id='.$rss_import_id );
			$this->ipsclass->main_msg = 'RSS Import Stream Edited';
		}
		
		if( $rss_import_enabled )
		{
			$this->rssimport_rebuild_cache( $rss_import_id, 0 );
		}
		
		$this->rssimport_overview();
	}
	
	/*-------------------------------------------------------------------------*/
	// RSS Import: Form
	/*-------------------------------------------------------------------------*/
	
	function rssimport_form( $type='add' )
	{
		//-----------------------------------------
		// Init Vars
		//-----------------------------------------
		
		$rss_import_id = isset($this->ipsclass->input['rss_import_id']) ? intval($this->ipsclass->input['rss_import_id']) : 0;
		
		//-------------------------------
		// Build forums drop down
		//-------------------------------
		
		$this->ipsclass->forums->forums_init();
						
		require_once( ROOT_PATH.'sources/lib/admin_forum_functions.php' );
		$aff               =  new admin_forum_functions();
		$aff->ipsclass     =& $this->ipsclass;
		$forum_dropdown    =  $aff->ad_forums_forum_list(1);
		
		//-----------------------------------------
		// Check (please?)
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			$formcode = 'rssimport_add_save';
			$title    = "Create New RSS Import Stream";
			$button   = "Create New RSS Import Stream";
			$rssstream = array( 'rss_import_topic_open' => 1, 
							    'rss_import_enabled' 	=> 1, 
							    'rss_import_showlink' 	=> '[url={url}]View the full article[/url]',
							    'rss_import_title'		=> '',
							    'rss_import_url'		=> '',
							    'rss_import_forum_id'	=> 0,
							    'rss_import_mid'		=> '',
							    'rss_import_pergo'		=> 10,
							    'rss_import_time'		=> '200',
							    'rss_import_topic_hide'	=> 0,
							    'rss_import_inc_pcount'	=> 1,
							    'rss_import_topic_pre'	=> '',
							    'rss_import_charset'	=> '',
							    'rss_import_allow_html'	=> 0,
							    'rss_import_auth'		=> NULL,
							    'rss_import_auth_user'	=> NULL,
							    'rss_import_auth_pass'	=> NULL,
							    'rss_import_id'			=> 0 );
		}
		else
		{
			$rssstream = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'rss_import', 'where' => 'rss_import_id='.$rss_import_id ) );
			
			if ( ! $rssstream['rss_import_id'] )
			{
				$this->ipsclass->main_msg = "No ID was passed, please try again.";
				$this->rssimport_overview();
				return;
			}
			
			//--------------------------------------------
			// Figure out MID
			//--------------------------------------------
			
			$member = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'id, members_display_name', 'from' => 'members', 'where' => "id=".intval($rssstream['rss_import_mid']) ) );
			
			if ( $member['id'] )
			{
				$rssstream['rss_import_mid'] = $member['members_display_name'];
			}
			
			$formcode = 'rssimport_edit_save';
			$title    = "Edit RSS Import Stream ".$rssstream['rss_import_title'];
			$button   = "Save Changes";
		}
		
		//-------------------------------
		// Form elements
		//-------------------------------
		
		$form = array();
		
		$form['rss_import_title']      = $this->ipsclass->adskin->form_input(        'rss_import_title'       , ( isset($_POST['rss_import_title']) 	 AND $_POST['rss_import_title'] )      ? stripslashes($_POST['rss_import_title'])      : $rssstream['rss_import_title'] );
		$form['rss_import_enabled']    = $this->ipsclass->adskin->form_yes_no(       'rss_import_enabled'     , ( isset($_POST['rss_import_enabled']) 	 AND $_POST['rss_import_enabled'] )    ? $_POST['rss_import_enabled']    : $rssstream['rss_import_enabled'] );
		$form['rss_import_url']        = $this->ipsclass->adskin->form_input(        'rss_import_url'         , ( isset($_POST['rss_import_url']) 		 AND $_POST['rss_import_url'] )        ? $_POST['rss_import_url']        : $rssstream['rss_import_url'] );
		$form['rss_import_forum_id']   = $this->ipsclass->adskin->form_dropdown(     'rss_import_forum_id'    , $forum_dropdown, ( isset($_POST['rss_import_forum_id']) AND $_POST['rss_import_forum_id'] ) ? $_POST['rss_import_forum_id'] : $rssstream['rss_import_forum_id'] );
		$form['rss_import_mid']        = $this->ipsclass->adskin->form_input(        'rss_import_mid'         , ( isset($_POST['rss_import_mid']) 		 AND $_POST['rss_import_mid'] )        ? $_POST['rss_import_mid']        : $rssstream['rss_import_mid'], 'text', "id='rss_import_mid'" );
		$form['rss_import_pergo']      = $this->ipsclass->adskin->form_simple_input( 'rss_import_pergo'       , ( isset($_POST['rss_import_pergo']) 	 AND  $_POST['rss_import_pergo'] )     ? $_POST['rss_import_pergo']      : $rssstream['rss_import_pergo'], 5 );
		$form['rss_import_time']       = $this->ipsclass->adskin->form_simple_input( 'rss_import_time'        , ( isset($_POST['rss_import_time']) 		 AND $_POST['rss_import_time'] )       ? $_POST['rss_import_time']       : $rssstream['rss_import_time'], 5 );
		$form['rss_import_showlink']   = $this->ipsclass->adskin->form_input(        'rss_import_showlink'    , ( isset($_POST['rss_import_showlink']) 	 AND $_POST['rss_import_showlink'] )   ? htmlspecialchars($_POST['rss_import_showlink'])   : htmlspecialchars($rssstream['rss_import_showlink']) );
		$form['rss_import_topic_open'] = $this->ipsclass->adskin->form_yes_no(       'rss_import_topic_open'  , ( isset($_POST['rss_import_topic_open']) AND $_POST['rss_import_topic_open'] ) ? $_POST['rss_import_topic_open'] : $rssstream['rss_import_topic_open'] );
		$form['rss_import_topic_hide'] = $this->ipsclass->adskin->form_yes_no(       'rss_import_topic_hide'  , ( isset($_POST['rss_import_topic_hide']) AND $_POST['rss_import_topic_hide'] ) ? $_POST['rss_import_topic_hide'] : $rssstream['rss_import_topic_hide'] );
		$form['rss_import_inc_pcount'] = $this->ipsclass->adskin->form_yes_no(       'rss_import_inc_pcount'  , ( isset($_POST['rss_import_inc_pcount']) AND $_POST['rss_import_inc_pcount'] ) ? $_POST['rss_import_inc_pcount'] : $rssstream['rss_import_inc_pcount'] );
		$form['rss_import_topic_pre']  = $this->ipsclass->adskin->form_input(        'rss_import_topic_pre'   , ( isset($_POST['rss_import_topic_pre'])  AND $_POST['rss_import_topic_pre'] )  ? $_POST['rss_import_topic_pre']  : $rssstream['rss_import_topic_pre'] );
		$form['rss_import_charset']    = $this->ipsclass->adskin->form_input(        'rss_import_charset'     , ( isset($_POST['rss_import_charset']) 	 AND $_POST['rss_import_charset'] )    ? $_POST['rss_import_charset']    : $rssstream['rss_import_charset'] );
		$form['rss_import_allow_html'] = $this->ipsclass->adskin->form_yes_no(       'rss_import_allow_html'  , ( isset($_POST['rss_import_allow_html']) AND $_POST['rss_import_allow_html'] ) ? $_POST['rss_import_allow_html'] : $rssstream['rss_import_allow_html'] );
		$form['rss_import_auth']	   = $this->ipsclass->adskin->form_checkbox(	 'rss_import_auth'		  ,
																						( isset($_POST['rss_import_auth']) AND $_POST['rss_import_auth'] ) ? $_POST['rss_import_auth'] : $rssstream['rss_import_auth'],
																						'1',
																						array( 0 => 'onclick="enable_auth_boxes()"', 1 => 'id="rss_import_auth"' )
																				);
		$auth_checked = ( isset($_POST['rss_import_auth']) AND $_POST['rss_import_auth'] ) ? $_POST['rss_import_auth'] : $rssstream['rss_import_auth'];
		if( !$auth_checked )
		{
			$form['rss_div_show'] = "style='display:none;'";
		}
		else
		{
			$form['rss_div_show'] = "style='display:;'";
		}
		
		$form['rss_import_auth_user']  = $this->ipsclass->adskin->form_input(        'rss_import_auth_user'   , ( isset($_POST['rss_import_auth_user']) AND $_POST['rss_import_auth_user'] )  ? $_POST['rss_import_auth_user']  : $rssstream['rss_import_auth_user'] );
		$form['rss_import_auth_pass']    = $this->ipsclass->adskin->form_input(        'rss_import_auth_pass'     , ( isset($_POST['rss_import_auth_pass']) AND $_POST['rss_import_auth_pass'] )    ? $_POST['rss_import_auth_pass']    : $rssstream['rss_import_auth_pass'] );																				
	
		$this->ipsclass->html .= $this->html->rss_import_form( $form, $title, $formcode, $button, $rssstream );
		
		$this->ipsclass->admin->page_title  = "RSS Import Manager";
		$this->ipsclass->admin->page_detail = "This section will allow you to manage your RSS Import feeds.";
		
		$this->ipsclass->admin->nav[]       = array( "", "Add/Edit RSS Import" );
		$this->ipsclass->admin->output();
	}
	
	/*-------------------------------------------------------------------------*/
	// List current RSS Imports
	/*-------------------------------------------------------------------------*/
	
	function rssimport_overview()
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
		
		$num = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'count(*) as row_count', 'from' => 'rss_import' ) );
		
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
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'rss_import', 'order' => 'rss_import_id ASC', 'limit' => array( $st, 25 ) ) );
		$this->ipsclass->DB->simple_exec();
		
		while( $r = $this->ipsclass->DB->fetch_row() )
		{
			//-------------------------------
			// (Alex) Cross
			//-------------------------------
			
			$r['_enabled_img'] = $r['rss_import_enabled'] ? 'aff_tick.png' : 'aff_cross.png';
			
			$content .= $this->html->rss_import_overview_row($r);
		}
		
		$this->ipsclass->html .= $this->html->rss_import_overview( $content, $page_links );
		
		$this->ipsclass->admin->page_title  = "RSS Import Manager";
		$this->ipsclass->admin->page_detail = "This section will allow you to manage your RSS Import feeds.";
		$this->ipsclass->admin->output();
	}
	
	
	/*-------------------------------------------------------------------------*/
	// RSS Import: Save
	/*-------------------------------------------------------------------------*/
	
	function rssimport_validate( $standalone=0 )
	{
		$return = 0;
		
		if( !$standalone )
		{
			//--------------------------------------------
			// INIT
			//--------------------------------------------
			
			$rss_import_id         = intval($this->ipsclass->input['rss_import_id']);
			$rss_import_title      = trim( $this->ipsclass->input['rss_import_title'] );
			$rss_import_url        = $this->ipsclass->txt_stripslashes( trim( $_POST['rss_import_url'] ) );
			$rss_import_mid        = trim( $this->ipsclass->input['rss_import_mid'] );
			$rss_import_showlink   = $this->ipsclass->txt_stripslashes( trim( $_POST['rss_import_showlink'] ) );
			$rss_import_enabled    = intval($this->ipsclass->input['rss_import_enabled']);
			$rss_import_forum_id   = intval($this->ipsclass->input['rss_import_forum_id']);
			$rss_import_pergo      = intval($this->ipsclass->input['rss_import_pergo']);
			$rss_import_time       = intval($this->ipsclass->input['rss_import_time']);
			$rss_import_topic_open = intval($this->ipsclass->input['rss_import_topic_open']);
			$rss_import_topic_hide = intval($this->ipsclass->input['rss_import_topic_hide']);
			$rss_import_inc_pcount = intval($this->ipsclass->input['rss_import_inc_pcount']);
			$rss_import_topic_pre  = $this->ipsclass->input['rss_import_topic_pre'];
			$rss_import_charset    = $this->ipsclass->input['rss_import_charset'];
			$rss_import_allow_html = intval($this->ipsclass->input['rss_import_allow_html']);
			$rss_import_auth	   = intval($this->ipsclass->input['rss_import_auth']);
			$rss_import_auth_user  = trim($this->ipsclass->input['rss_import_auth_user']) ? trim($this->ipsclass->input['rss_import_auth_user']) : 'Not Needed';
			$rss_import_auth_pass  = trim($this->ipsclass->input['rss_import_auth_pass']) ? trim($this->ipsclass->input['rss_import_auth_pass']) : 'Not Needed';
			
			$return				   = 1;
		}
		else
		{
			$return = 0;
			
			$rss_input_id = isset($this->ipsclass->input['rss_id']) ? intval($this->ipsclass->input['rss_id']) : 0;
			
			if( $rss_input_id > 0 )
			{
				$rss_data = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'rss_import', 'where' => 'rss_import_id='.$rss_input_id ) );
				
				if( !$rss_data['rss_import_url'] )
				{
					$rss_import_url 		= "";
					$rss_import_auth 		= "";
					$rss_import_auth_user 	= "";
					$rss_import_auth_pass 	= "";
				}
				else
				{
					$standalone = 0;
					
					$rss_import_id         = intval($rss_data['rss_import_id']);
					$rss_import_url        = $rss_data['rss_import_url'];
					
					$member = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'members_display_name', 'from' => 'members', 'where' => 'id='.$rss_data['rss_import_mid'] ) );
					
					$rss_import_mid		   = $member['members_display_name'];
					
					$rss_import_forum_id   = intval($rss_data['rss_import_forum_id']);
					$rss_import_inc_pcount = intval($rss_data['rss_import_inc_pcount']);
					$rss_import_charset    = $rss_data['rss_import_charset'];
					$rss_import_auth	   = intval($rss_data['rss_import_auth']);
					$rss_import_auth_user  = trim($rss_data['rss_import_auth_user']);
					$rss_import_auth_pass  = trim($rss_data['rss_import_auth_pass']);
				}
			}
			else
			{
				$rss_import_url 		= $this->ipsclass->txt_stripslashes( trim( $_POST['rss_url'] ) );
				$rss_import_charset 	= $this->ipsclass->vars['gb_char_set'];
				$rss_import_auth		= "";
				$rss_import_auth_user 	= "";
				$rss_import_auth_pass 	= "";				
			}
		}
		
		if( !$rss_import_url )
		{
			$this->validate_msg[] = $this->html->rss_validate_msg( array( 'msg' => "There was no url entered to validate" ) );
		}
		else
		{
			//--------------------------------------------
			// INIT
			//--------------------------------------------
			
			if ( ! $this->classes_loaded )
			{
				//--------------------------------------------
				// Require classes
				//--------------------------------------------
				
				if ( ! is_object( $this->class_rss ) )
				{
					require_once( KERNEL_PATH . 'class_rss.php' );
					$this->class_rss               =  new class_rss();
					$this->class_rss->ipsclass     =& $this->ipsclass;
					$this->class_rss->use_sockets  =  $this->use_sockets;
					$this->class_rss->rss_max_show =  100;
				}
				
				$this->classes_loaded = 1;
			}
			
			//--------------------------------------------
			// Set this import's doctype
			//--------------------------------------------
			
			$this->class_rss->doc_type 		= $this->ipsclass->vars['gb_char_set'];
			$this->class_rss->feed_charset 	= $this->ipsclass->vars['gb_char_set'];
			
			if( strtolower($rss_import_charset) != $this->ipsclass->vars['gb_char_set'] )
			{
				$this->class_rss->convert_charset = 1;
			}
			else
			{
				$this->class_rss->convert_charset = 0;
			}
			
			//--------------------------------------------
			// Set this import's authentication
			//--------------------------------------------				
			$this->class_rss->auth_req = $rss_import_auth;				
			$this->class_rss->auth_user = $rss_import_auth_user;
			$this->class_rss->auth_pass = $rss_import_auth_pass;
			
			//--------------------------------------------
			// Clear RSS object's error cache first
			//--------------------------------------------
			$this->class_rss->errors 	= array();
			$this->class_rss->rss_items = array();
			
			//--------------------------------------------				
			// Reset the rss count as this is a new feed
			//--------------------------------------------
							
			$this->class_rss->rss_count =  0;
			
			//--------------------------------------------
			// Parse RSS
			//--------------------------------------------
			
			$this->class_rss->rss_parse_feed_from_url( $rss_import_url );
			
			//--------------------------------------------
			// Validate Data - HTTP Status Code/Text
			//--------------------------------------------
			
			if( $this->class_rss->class_file_management->http_status_code != "200" )
			{
				if( $this->class_rss->class_file_management->http_status_code )
				{
					$this->validate_msg[] =	$this->html->rss_validate_msg( array( 'class' => 'rss-feed-invalid', 
																				  'msg' => "HTTP Status Code: ".$this->class_rss->class_file_management->http_status_code." (".$this->class_rss->class_file_management->http_status_text.")" ) );
				}
			}
			else
			{
				$this->validate_msg[] = $this->html->rss_validate_msg( array( 'class' => 'rss-feed-valid', 
																			  'msg' => "HTTP Status Code: ".$this->class_rss->class_file_management->http_status_code." (".$this->class_rss->class_file_management->http_status_text.")" ) );
			}
			
			if ( is_array( $this->class_rss->errors ) and count( $this->class_rss->errors ) )
			{
				foreach( $this->class_rss->errors as $error )
				{
					$this->validate_msg[] = $this->html->rss_validate_msg( array( 'class' => 'rss-feed-invalid', 'msg' => $error ) );
				}
			}
			else
			{
				if( $this->class_rss->orig_doc_type )
				{
					if( !$standalone AND $rss_import_charset )
					{
						if( strtolower($rss_import_charset) != strtolower($this->class_rss->orig_doc_type) )
						{
							$this->validate_msg[] = $this->html->rss_validate_msg( array( 'class' => 'rss-feed-invalid', 
																						  'msg' => "The RSS feed's charset is <i>{$this->class_rss->orig_doc_type}</i> but you entered <i>{$rss_import_charset}</i>" ) );
						}
						else
						{
							$this->validate_msg[] = $this->html->rss_validate_msg( array( 'class' => 'rss-feed-valid', 
																						  'msg' => "The RSS feed's charset is <i>{$this->class_rss->orig_doc_type}</i> (correct)" ) );
						}
					}
					else
					{
						$this->validate_msg[] = $this->html->rss_validate_msg( array( 'class' => 'rss-feed-valid', 
																					  'msg' => "The RSS feed's charset is <i>{$this->class_rss->orig_doc_type}</i>" ) );
					}
				}
				else
				{
					$this->validate_msg[] = $this->html->rss_validate_msg( array( 'class' => 'rss-feed-invalid', 
																				  'msg' => "We could not determine the feed's character set" ) );
				}
				
				//--------------------------------------------
				// Any Channels?
				//--------------------------------------------
				
				if ( ! is_array( $this->class_rss->rss_channels ) or ! count( $this->class_rss->rss_channels ) )
				{
					$this->validate_msg[] = $this->html->rss_validate_msg( array( 'class' => 'rss-feed-invalid', 
																				  'msg' => "We could not find any channels in the RSS feed (nothing to import)." ) );
				}
				else
				{
					$this->validate_msg[] = $this->html->rss_validate_msg( array( 'class' => 'rss-feed-valid', 
																				  'msg' => "We found ".count($this->class_rss->rss_channels)." channel(s) in the RSS feed." ) );
					
					//--------------------------------------------
					// Any Items?
					//--------------------------------------------

					if ( ! is_array( $this->class_rss->rss_items ) or ! count( $this->class_rss->rss_items ) )
					{
						$this->validate_msg[] = $this->html->rss_validate_msg( array( 'class' => 'rss-feed-invalid', 
																					  'msg' => "We could not find any actual content (topics, news articles, etc.) (nothing to import)" ) );
					}
					else
					{
						foreach ( $this->class_rss->rss_channels as $channel_id => $channel_data )
						{
							if ( is_array( $this->class_rss->rss_items[ $channel_id ] ) and count ($this->class_rss->rss_items[ $channel_id ] ) )
							{
								$this->validate_msg[] = $this->html->rss_validate_msg( array( 'class' => 'rss-feed-valid', 
																						  	  'msg' => "We found ".count($this->class_rss->rss_items[ $channel_id ])." article(s)/topic(s) in the RSS feed." ) );
																
								foreach( $this->class_rss->rss_items[ $channel_id ] as $item_data )
								{
									if( !$item_data['unixdate'] )
									{
										$this->validate_msg[] = $this->html->rss_validate_msg( array( 'class' => 'rss-feed-msg', 
																									  'msg' => "A date was not found in at least one of the RSS articles - current time would be used instead." ) );
									}
									
									if ( $item_data['unixdate'] < 1 )
									{
										$this->validate_msg[] = $this->html->rss_validate_msg( array( 'class' => 'rss-feed-msg', 
																									  'msg' => "An invalid date was found in at least one of the RSS articles - current time would be used instead." ) );
									}
									else if ( $item_data['unixdate'] > time() )
									{
										$this->validate_msg[] = $this->html->rss_validate_msg( array( 'class' => 'rss-feed-msg',
																									  'msg' => "An invalid date was found in at least one of the RSS articles - current time would be used instead." ) );
									}	
									
									$item_data['content']  = $item_data['content']   ? $item_data['content']  : $item_data['description'];								
									
									if ( ! $item_data['title'] OR ! $item_data['content'] )
									{
										$this->validate_msg[] = $this->html->rss_validate_msg( array( 'class' => 'rss-feed-invalid', 
																									  'msg' => "At least one article was missing a title or a description/content field. If all articles are missing both fields, nothing would import. Otherwise IPB would skip just the items missing both a title and content." ) );
									}
									
									break 2;
								}
							}
						}
					}
				}
			}
			
			if( !$standalone )
			{
				if( $rss_import_mid )
				{
					$member = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'id, name', 'from' => 'members', 'where' => "members_l_display_name='{$rss_import_mid}'" ) );
					
					if ( ! $member['id'] )
					{
						$this->validate_msg[] = $this->html->rss_validate_msg( array( 'class' => 'rss-feed-invalid', 
																					  'msg' => "The specified member '{$rss_import_mid}' could not be found." ) );
					}
				}
				else
				{
					$this->validate_msg[] = $this->html->rss_validate_msg( array( 'class' => 'rss-feed-invalid', 
																				  'msg' => "We could not validate the member used to import articles." ) );
				}					
			}
			
			//--------------------------------------------
			// Init forums if not already done so
			//--------------------------------------------
			
			if ( ! is_array( $this->ipsclass->forums->forum_by_id ) OR !count( $this->ipsclass->forums->forum_by_id ) )
			{
				$this->ipsclass->forums->forums_init();
			}			
			
			if( !$standalone AND $rss_import_forum_id )
			{
				if ( ! $this->ipsclass->forums->forum_by_id[ $rss_import_forum_id ] )
				{
					$this->validate_msg[] = $this->html->rss_validate_msg( array( 'class' => 'rss-feed-invalid', 
																				  'msg' => "The specified forum could not be found." ) );
				}
				else
				{
					if ( $this->ipsclass->forums->forum_by_id[ $rss_import_forum_id ]['sub_can_post'] != 1 OR $this->ipsclass->forums->forum_by_id[ $rss_import_forum_id ]['redirect_on'] == 1 )
					{
						$this->validate_msg[] = $this->html->rss_validate_msg( array( 'class' => 'rss-feed-invalid', 
																					  'msg' => "The specified forum is either a redirect forum, or a category, and cannot display new topics." ) );
					}
					
					if( $rss_import_inc_pcount AND !$this->ipsclass->forums->forum_by_id[ $rss_import_forum_id ]['inc_postcount'] )
					{
						$this->validate_msg[] = $this->html->rss_validate_msg( array( 'class' => 'rss-feed-invalid', 
																					  'msg' => "The specified forum does not allow posts to increment user's post count - importer's post count will <i>not</i> be incremented." ) );
					}
				}
			}

			//--------------------------------------------
			// Display?
			//--------------------------------------------
			
			if ( !$return )
			{
				if( count($this->validate_msg) )
				{
					$this->ipsclass->main_msg = "<b>Validation Results for <span class='rss-feed-url'>".$rss_import_url."</span></b><br />&nbsp;&middot;".implode( "<br />&nbsp;&middot;", $this->validate_msg );
					$this->rssimport_overview();
					return;
				}
			}
			else
			{
				return TRUE;
			}
		}	
	}

}


?>