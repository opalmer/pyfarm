<?php

/*
+--------------------------------------------------------------------------
|   Invision Power Board
|   ========================================
|   by Matthew Mecham
|   (c) 2004 Invision Power Services
|   http://www.invisionboard.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Email: matt@invisionpower.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
|
|   > Admin: Attachment Functions
|   > Module written by Matt Mecham
|   > Date started: Saturday 13th March
|   > (Ooh, 13th! Note: First ever IPB module to be started
|   >  on a Saturday!)
|
|	> Module Version Number: 1.0.0
|   > DBA Checked: Mon 24th May 2004
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}


class ad_attachments {

	var $base_url;
	var $ipsclass;
	var $html;
	var $image_dir;
	
	/**
	* Section title name
	*
	* @var	string
	*/
	var $perm_main = "content";
	
	/**
	* Section title name
	*
	* @var	string
	*/
	var $perm_child = "attach";
	
	function auto_run()
	{
		$this->html = $this->ipsclass->acp_load_template('cp_skin_attachments');
		
		$this->ipsclass->admin->nav[] = array( $this->ipsclass->form_code, 'Attachments Manager' );
		
		$this->ipsclass->admin->page_detail = "This section will allow you to manage your member's attachments and attachment permissions";
		$this->ipsclass->admin->page_title  = "Attachments Manager";
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'set_image_dir', 'from' => 'skin_sets', 'where' => 'set_default=1' ) );
		$this->ipsclass->DB->simple_exec();
		
		$image_set = $this->ipsclass->DB->fetch_row();
		$this->image_dir = $image_set['set_image_dir'];
		
		//-----------------------------------------
		// StRT!
		//-----------------------------------------

		switch( $this->ipsclass->input['code'] )
		{
			case 'types':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->attach_type_start();
				break;
			case 'attach_add':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->attach_type_form('add');
				break;
			case 'attach_doadd':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':add' );
				$this->attach_type_save('add');
				break;
			case 'attach_edit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->attach_type_form('edit');
				break;
			case 'attach_delete':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':remove' );
				$this->attach_type_delete();
				break;
			case 'attach_doedit':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':edit' );
				$this->attach_type_save('edit');
				break;
			case 'attach_export':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':export' );
				$this->attach_type_export();
				break;
			case 'attach_import':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':import' );
				$this->attach_type_import();
				break;
			//-----------------------------------------
			// Stats
			//-----------------------------------------
			case 'stats':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':stats' );
				$this->attach_stats_start();
				break;
			//-----------------------------------------
			// Bulk Remove
			//-----------------------------------------	
			case 'attach_bulk_remove':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':remove' );
				$this->attach_bulk_remove();
				break;
			//-----------------------------------------
			// Search
			//-----------------------------------------	
			case 'search':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':search' );
				$this->attach_search_start();
				break;
			case 'attach_search_complete':
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':search' );
				$this->attach_search_complete();
				break;
			case 'master_xml_export':
				$this->master_xml_export();
				break;
			//-----------------------------------------
			// Default:
			//-----------------------------------------
			default:
				$this->ipsclass->admin->cp_permission_check( $this->perm_main.'|'.$this->perm_child.':' );
				$this->attach_type_start();
				break;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Export Master XML
	/*-------------------------------------------------------------------------*/
	
	function master_xml_export()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$entry = array();
		
		//-----------------------------------------
		// Get XML class
		//-----------------------------------------
		
		require_once( KERNEL_PATH.'class_xml.php' );
		
		$xml = new class_xml();
		
		$xml->doc_type = $this->ipsclass->vars['gb_char_set'];

		$xml->xml_set_root( 'export', array( 'exported' => time() ) );
		
		//-----------------------------------------
		// Set group
		//-----------------------------------------
		
		$xml->xml_add_group( 'group' );
		
		//-----------------------------------------
		// Get templates...
		//-----------------------------------------
	
		$this->ipsclass->DB->simple_construct( array( 'select' => '*',
													  'from'   => 'attachments_type'  ) );
		
		$this->ipsclass->DB->simple_exec();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$content = array();
			
			//-----------------------------------------
			// Sort the fields...
			//-----------------------------------------
			
			foreach( $r as $k => $v )
			{
				$content[] = $xml->xml_build_simple_tag( $k, $v );
			}
			
			$entry[] = $xml->xml_build_entry( 'row', $content );
		}
		
		$xml->xml_add_entry_to_group( 'group', $entry );
		
		$xml->xml_format_document();
		
		$doc = $xml->xml_document;
		
		//-----------------------------------------
		// Print to browser
		//-----------------------------------------
		
		$this->ipsclass->admin->show_download( $doc, 'attachments.xml', '', 0 );
	}
	
	//-----------------------------------------
	//
	// SEARCH: Complete
	//
	//-----------------------------------------
	
	function attach_search_complete()
	{
		$show = intval($this->ipsclass->input['show']);
		
		$show = $show > 100 ? 100 : $show;
		
		//-----------------------------------------
		// Get attachment details
		//-----------------------------------------
		
		$this->ipsclass->cache['attachtypes'] = array();
			
		$this->ipsclass->DB->simple_construct( array( 'select' => 'atype_extension,atype_mimetype,atype_post,atype_photo,atype_img', 'from' => 'attachments_type', 'where' => "atype_photo=1 OR atype_post=1" ) );
		$this->ipsclass->DB->simple_exec();
	
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->cache['attachtypes'][ $r['atype_extension'] ] = $r;
		}
		
		//-----------------------------------------
		// Build URL
		//-----------------------------------------
		
		$url = "";
		$url_components = array( 'extension', 'filesize', 'filesize_gt', 'days', 'days_gt', 'hits', 'hits_gt', 'filename', 'authorname', 'onlyimage' );
		
		foreach( $url_components as $u )
		{
			$url .= $u.'='.$this->ipsclass->input[ $u ].'&';
		}
		
		$url .= 'orderby='.$this->ipsclass->input['orderby'].'&sort='.$this->ipsclass->input['sort'].'&show='.$this->ipsclass->input['show'];
		
		//-----------------------------------------
		// Build Query
		//-----------------------------------------
		
		$queryfinal = "";
		$query      = array();
		
		if ( $this->ipsclass->input['extension'] )
		{
			$query[] = 'a.attach_ext="'.strtolower( str_replace( ".", "", $this->ipsclass->input['extension'] ) ).'"';
		}
		
		if ( $this->ipsclass->input['filesize'] )
		{
			$gt = $this->ipsclass->input['filesize_gt'] == 'gt' ? '>=' : '<';
			
			$query[] = "a.attach_filesize $gt ".intval($this->ipsclass->input['filesize']*1024);
		}
		
		if ( $this->ipsclass->input['days'] )
		{
			$day_break = time() - intval( $this->ipsclass->input['days'] * 86400 );
			
			$gt = $this->ipsclass->input['days_gt'] == 'lt' ? '>=' : '<';
			
			$query[] = "a.attach_date $gt ".$day_break;
		}
		
		if ( $this->ipsclass->input['hits'] )
		{
			$gt = $this->ipsclass->input['hits_gt'] == 'gt' ? '>=' : '<';
			
			$query[] = "a.attach_hits $gt ".intval($this->ipsclass->input['hits']);
		}
		
		if ( $this->ipsclass->input['filename'] )
		{
			$query[] = 'LOWER(a.attach_file) LIKE "%'.strtolower( $this->ipsclass->input['filename'] ).'%"';
		}
		
		if ( $this->ipsclass->input['authorname'] )
		{
			$query[] = 'LOWER(p.author_name) LIKE "%'.strtolower( $this->ipsclass->input['authorname'] ).'%"';
		}
		
		if ( $this->ipsclass->input['onlyimage'] )
		{
			$query[] = 'a.attach_is_image=1';
		}
		
		if ( count($query) )
		{
			$queryfinal = 'AND '. implode( " AND ", $query );
		}
		
		$this->ipsclass->DB->build_query( array( 'select'   => 'a.*',
												 'from'     => array( 'attachments' => 'a' ),
												 'where'    => "attach_rel_module='post'".$queryfinal,
												 'add_join' => array(
												 # POST TABLE JOIN
												 					  1 => array( 'select' => 'p.author_id, p.author_name, p.post_date',
												 					  			  'from'   => array( 'posts' => 'p' ),
												 					  			  'where'  => 'p.pid=a.attach_rel_id',
												 					  			  'type'   => 'left' ),
												 # TOPIC TABLE JOIN 					  			  
												 					  0 => array( 'select' => 't.tid, t.forum_id, t.title',
												 								  'from'   => array( 'topics' => 't' ),
												 								  'where'  => 'p.topic_id=t.tid',
												 								  'type'   => 'left' ),
												 # MEMBER TABLE JOIN
												 					  2 => array( 'select' => 'm.members_display_name',
												 					  			  'from'   => array( 'members' => 'm' ),
												 					  			  'where'  => 'm.id=a.attach_member_id',
												 					  			  'type'   => 'left' )
												 					 ),
												 'order'	=> "a.attach_{$this->ipsclass->input['orderby']} {$this->ipsclass->input['sort']}",
												 'limit'    => array( 0, $show ) ) );
												 
		$this->ipsclass->DB->exec_query();
												 					 		 
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , 'attach_bulk_remove'   ),
																			 2 => array( 'act'   , 'attach'  ),
																			 3 => array( 'return', 'search'   ),
																			 4 => array( 'url'   , $url      ),
																			 5 => array( 'section', $this->ipsclass->section_code ),
																	)      );
									                    		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"    , "1%" );
		$this->ipsclass->adskin->td_header[] = array( "Attachment", "20%" );
		$this->ipsclass->adskin->td_header[] = array( "Size"      , "10%" );
		$this->ipsclass->adskin->td_header[] = array( "Author"    , "15%" );
		$this->ipsclass->adskin->td_header[] = array( "Topic"     , "25%" );
		$this->ipsclass->adskin->td_header[] = array( "Posted    ", "25%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"    , "1%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Attachments: Search Results" );

		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$r['stitle'] = $this->ipsclass->txt_truncate($r['title'], 30);
			
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<img src='{$this->ipsclass->vars['board_url']}/style_images/{$this->image_dir}/{$this->ipsclass->cache['attachtypes'][ $r['attach_ext'] ]['atype_img']}' border='0' />" ,
																	 "<a href='{$this->ipsclass->vars['board_url']}/index.php?{$this->ipsclass->form_code}&type=post&id={$r['attach_id']}' target='_blank'>{$r['attach_file']}</a>",
																     $this->ipsclass->size_format($r['attach_filesize']),
																     $r['members_display_name'],
																     "<a href='{$this->ipsclass->vars['board_url']}/index.php?showtopic={$r['tid']}&view=findpost&p={$r['attach_rel_id']}' target='_blank' title='{$r['title']}'>{$r['stitle']}</a>",
																     $this->ipsclass->get_date( $r['post_date'], 'SHORT', 1 ),
																     "<div align='center'><input type='checkbox' name='attach_{$r['attach_id']}' value='1' /></div>",
													        )      );
		}
		
		$removebutton = "<input type='submit' value='Delete Checked Attachments' class='realdarkbutton'></form>";
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic( $removebutton, "right", "tablesubheader");
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		//-----------------------------------------
		// PRINT
		//-----------------------------------------
		
		$this->ipsclass->admin->output();
		
	}
	
	//-----------------------------------------
	//
	// SEARCH: Start
	//
	//-----------------------------------------
	
	function attach_search_start()
	{
		//-----------------------------------------
		// HEADER
		//-----------------------------------------
		
		$this->ipsclass->html .= "<script type='text/javascript' src='{$this->ipsclass->vars['board_url']}/jscripts/ipb_xhr_findnames.js'></script>
									<div id='ipb-get-members' style='border:1px solid #000; background:#FFF; padding:2px;position:absolute;width:170px;display:none;z-index:100'></div>";
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;" , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;" , "60%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Search Attachments" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , 'attach_search_complete' ),
																			 2 => array( 'act'   , 'attach'  ),
																			 3 => array( 'section', $this->ipsclass->section_code ),
																	)      );
									                    
		$gt_array = array( 0 => array( 'gt', 'More Than' ), 1 => array( 'lt', 'Less Than' ) );
		
		//-----------------------------------------
		// FORM
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Match File Extension</b><div style='color:gray'>Leave blank to omit</div>",
												 				 $this->ipsclass->adskin->form_simple_input( 'extension', isset($_POST['extension']) ? $_POST['extension'] : '', 10 ),
														)      );
														
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Match File Size (in kb)</b><div style='color:gray'>Leave blank to omit</div>",
																 $this->ipsclass->adskin->form_dropdown( 'filesize_gt', $gt_array, isset($_POST['filesize_gt']) ? $_POST['filesize_gt'] : '' ).' '.
												 				 $this->ipsclass->adskin->form_simple_input( 'filesize', isset($_POST['filesize']) ? $_POST['filesize'] : '', 10 ),
														)      );
														
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Match Posted <em>n</em> Days</b><div style='color:gray'>Leave blank to omit</div>",
																 $this->ipsclass->adskin->form_dropdown( 'days_gt', $gt_array, isset($_POST['days_gt']) ? $_POST['days_gt'] : '' ).' '.
												 				 $this->ipsclass->adskin->form_simple_input( 'days', isset($_POST['days']) ? $_POST['days'] : '', 10 ).' ago',
														)      );
														
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Match Viewed <em>n</em> Times</b><div style='color:gray'>Leave blank to omit</div>",
																 $this->ipsclass->adskin->form_dropdown( 'hits_gt', $gt_array, isset($_POST['hits_gt']) ? $_POST['hits_gt'] : '' ).' '.
												 				 $this->ipsclass->adskin->form_simple_input( 'hits', isset($_POST['hits']) ? $_POST['hits'] : '', 10 ).' times',
														)      );
														
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Match File Name</b><div style='color:gray'>Leave blank to omit</div>",
												 				 $this->ipsclass->adskin->form_simple_input( 'filename', isset($_POST['filename']) ? $_POST['filename'] : '', 30 ),
														)      );
														
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Match Post Author Name</b><div style='color:gray'>Leave blank to omit</div>",
												 				 $this->ipsclass->adskin->form_input( 'authorname', isset($_POST['authorname']) ? $_POST['authorname'] : '', 'text', "id='authorname'" ),
														)      );
														
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Match Only Images?</b><div style='color:gray'>If 'yes', this search will only return image attachments.</div>",
												 				 $this->ipsclass->adskin->form_yes_no( 'onlyimage', isset($_POST['onlyimage']) ? $_POST['onlyimage'] : '', 30 ),
														)      );
														
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Order Results By</b>",
																 $this->ipsclass->adskin->form_dropdown( 'orderby', array( 0 => array( 'date'    , 'Attach Date'      ),
																 										             1 => array( 'hits'    , 'Attach Views'     ),
																 										             2 => array( 'filesize', 'Attach File Size' ),
																 										             3 => array( 'file'    , 'Attach File Name' ),
																 										           ), isset($_POST['orderby']) ? $_POST['orderby'] : '' ).' '.
																 $this->ipsclass->adskin->form_dropdown( 'sort'   , array( 0 => array( 'desc'   , 'Descending [9-0]'  ),
																 													 1 => array( 'asc'    , 'Ascending [0-9]'   ),
																 										           ), isset($_POST['sort']) ? $_POST['sort'] : '' )
																 										        
												 				 
														)      );
														
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Show <em>n</em> Results</b><div style='color:gray'>Maximum is 100 regardless of your entry</div>",
												 				 $this->ipsclass->adskin->form_simple_input( 'show', isset($_POST['show']) ? $_POST['show'] : 25, 10 ),
														)      );
														
		
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Search");
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->html .= "<script type='text/javascript'>
									// INIT find names
									init_js( 'theAdminForm', 'authorname');
									// Run main loop
									var tmp = setTimeout( 'main_loop()', 10 );
								</script>";		
		
		//-----------------------------------------
		//-------------------------------
		
		$this->ipsclass->admin->output();
	}
	
	//-----------------------------------------
	//
	// BULK REMOVE
	//
	//-----------------------------------------
	
	function attach_bulk_remove()
	{
		foreach ($this->ipsclass->input as $key => $value)
		{
			if ( preg_match( "/^attach_(\d+)$/", $key, $match ) )
			{
				if ( $this->ipsclass->input[$match[0]] )
				{
					$ids[] = $match[1];
				}
			}
		}
		
		$ids = $this->ipsclass->clean_int_array( $ids );
		
		$attach_tid = array();
		
		if ( count( $ids ) )
		{
			//-----------------------------------------
			// Get attach details?
			//-----------------------------------------
			
			$this->ipsclass->DB->cache_add_query( 'attachments_bulk_remove', array( 'ids' => $ids ) );
			$this->ipsclass->DB->cache_exec_query();
			
			$attach_ids = array();
			
			while ( $killmeh = $this->ipsclass->DB->fetch_row() )
			{
				if ( $killmeh['attach_location'] )
				{
					@unlink( $this->ipsclass->vars['upload_dir']."/".$killmeh['attach_location'] );
				}
				if ( $killmeh['attach_thumb_location'] )
				{
					@unlink( $this->ipsclass->vars['upload_dir']."/".$killmeh['attach_thumb_location'] );
				}
				
				$attach_tid[ $killmeh['topic_id'] ] = $killmeh['topic_id'];
			}
			
			$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'attachments', 'where' => "attach_id IN(".implode(",",$ids).")" ) );
			
			//-----------------------------------------
			// Recount topic upload marker
			//-----------------------------------------
			
			require_once( ROOT_PATH.'sources/classes/post/class_post.php' );
			
			$postlib = new class_post();
			$postlib->ipsclass =& $this->ipsclass;
			
			foreach( $attach_tid as $tid )
			{
				$postlib->pf_recount_topic_attachments($tid);
			}
		}
		
		$this->ipsclass->main_msg = "Attachments Removed";
		
		if ( $this->ipsclass->input['return'] == 'stats' )
		{
			$this->attach_stats_start();
		}
		else
		{
			if ( $_POST['url'] )
			{
				foreach( explode( '&', $_POST['url'] ) as $u )
				{
					list ( $k, $v ) = explode( '=', $u );
					
					$this->ipsclass->input[ $k ] = $v;
				}
			}
			
			$this->attach_search_complete();
		}
	}
	
	//-----------------------------------------
	//
	// STATS: Start
	//
	//-----------------------------------------
	
	function attach_stats_start()
	{
		//-----------------------------------------
		// Get attachment details
		//-----------------------------------------
		
		$this->ipsclass->cache['attachtypes'] = array();
			
		$this->ipsclass->DB->simple_construct( array( 'select' => 'atype_extension,atype_mimetype,atype_post,atype_photo,atype_img', 'from' => 'attachments_type', 'where' => "atype_photo=1 OR atype_post=1" ) );
		$this->ipsclass->DB->simple_exec();
	
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->cache['attachtypes'][ $r['atype_extension'] ] = $r;
		}
		
		$this->ipsclass->adskin->td_header[] = array( "{none}", "30%" );
		$this->ipsclass->adskin->td_header[] = array( "{none}", "20%" );
		$this->ipsclass->adskin->td_header[] = array( "{none}", "30%" );
		$this->ipsclass->adskin->td_header[] = array( "{none}", "20%" );
		
		//-----------------------------------------
		// Get quick stats
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Attachments: Overview" );
		
		$stats = $this->ipsclass->DB->simple_exec_query( array( 'select' => 'count(*) as count, sum(attach_filesize) as sum',
 																'from'   => 'attachments' ) );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Number of Attachments</b>" , $this->ipsclass->do_number_format($stats['count']),
																 			 "<b>Attachments Disk Usage</b>", $this->ipsclass->size_format($stats['sum']),
													  				 )      );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , 'attach_bulk_remove'   ),
																			 2 => array( 'act'   , 'attach'  ),
																			 3 => array( 'return', 'stats'   ),
																			 4 => array( 'section', $this->ipsclass->section_code ),
																	)      );
									                    
		//-----------------------------------------
		// Recent 5 Attachments
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"    , "1%" );
		$this->ipsclass->adskin->td_header[] = array( "Attachment", "20%" );
		$this->ipsclass->adskin->td_header[] = array( "Size"      , "10%" );
		$this->ipsclass->adskin->td_header[] = array( "Author"    , "15%" );
		$this->ipsclass->adskin->td_header[] = array( "Topic"     , "25%" );
		$this->ipsclass->adskin->td_header[] = array( "Posted    ", "25%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"    , "1%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Attachments: Last 5 Attached" );
		
		$this->ipsclass->DB->build_query( array( 'select'   => 'a.*',
												 'from'     => array( 'attachments' => 'a' ),
												 'where'    => "attach_rel_module='post'",
												 'add_join' => array(
												 # POST TABLE JOIN
												 					  1 => array( 'select' => 'p.author_id, p.author_name, p.post_date',
												 					  			  'from'   => array( 'posts' => 'p' ),
												 					  			  'where'  => 'p.pid=a.attach_rel_id',
												 					  			  'type'   => 'left' ),
												 # TOPIC TABLE JOIN 					  			  
												 					  0 => array( 'select' => 't.tid, t.forum_id, t.title',
												 								  'from'   => array( 'topics' => 't' ),
												 								  'where'  => 'p.topic_id=t.tid',
												 								  'type'   => 'left' ),
												 # MEMBER TABLE JOIN
												 					  2 => array( 'select' => 'm.members_display_name',
												 					  			  'from'   => array( 'members' => 'm' ),
												 					  			  'where'  => 'm.id=a.attach_member_id',
												 					  			  'type'   => 'left' )
												 					 ),
												 'order'	=> "a.attach_date DESC",
												 'limit'    => array( 0, 5 ) ) );
												 
		$this->ipsclass->DB->exec_query();
	
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$r['stitle'] = $this->ipsclass->txt_truncate($r['title'], 30);
			
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<img src='{$this->ipsclass->vars['board_url']}/style_images/{$this->image_dir}/{$this->ipsclass->cache['attachtypes'][ $r['attach_ext'] ]['atype_img']}' border='0' />" ,
																				 "<a href='{$this->ipsclass->vars['board_url']}/index.php?act=attach&type=post&id={$r['attach_id']}' target='_blank'>{$r['attach_file']}</a>",
																				 $this->ipsclass->size_format($r['attach_filesize']),
																				 $r['members_display_name'],
																				 "<a href='{$this->ipsclass->vars['board_url']}/index.php?showtopic={$r['tid']}&view=findpost&p={$r['attach_rel_id']}' target='_blank' title='{$r['title']}'>{$r['stitle']}</a>",
																				 $this->ipsclass->get_date( $r['post_date'], 'SHORT', 1 ),
																				 "<div align='center'><input type='checkbox' name='attach_{$r['attach_id']}' value='1' /></div>",
																		)      );
		}
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		//-----------------------------------------
		// Largest 5 Attachments
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"    , "1%" );
		$this->ipsclass->adskin->td_header[] = array( "Attachment", "20%" );
		$this->ipsclass->adskin->td_header[] = array( "Size"      , "10%" );
		$this->ipsclass->adskin->td_header[] = array( "Author"    , "15%" );
		$this->ipsclass->adskin->td_header[] = array( "Topic"     , "25%" );
		$this->ipsclass->adskin->td_header[] = array( "Posted    ", "25%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"    , "1%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Attachments: Largest 5 Topic Attachments" );
		
		$this->ipsclass->DB->build_query( array( 'select'   => 'a.*',
												 'from'     => array( 'attachments' => 'a' ),
												 'where'    => "attach_rel_module='post'",
												 'add_join' => array(
												 # POST TABLE JOIN
												 					  1 => array( 'select' => 'p.author_id, p.author_name, p.post_date',
												 					  			  'from'   => array( 'posts' => 'p' ),
												 					  			  'where'  => 'p.pid=a.attach_rel_id',
												 					  			  'type'   => 'left' ),
												 # TOPIC TABLE JOIN 					  			  
												 					  0 => array( 'select' => 't.tid, t.forum_id, t.title',
												 								  'from'   => array( 'topics' => 't' ),
												 								  'where'  => 'p.topic_id=t.tid',
												 								  'type'   => 'left' ),
												 # MEMBER TABLE JOIN
												 					  2 => array( 'select' => 'm.members_display_name',
												 					  			  'from'   => array( 'members' => 'm' ),
												 					  			  'where'  => 'm.id=a.attach_member_id',
												 					  			  'type'   => 'left' )
												 					 ),
												 'order'	=> "a.attach_filesize DESC",
												 'limit'    => array( 0, 5 ) ) );
												 
		$this->ipsclass->DB->exec_query();
	
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$r['stitle'] = $this->ipsclass->txt_truncate($r['title'], 30);
			
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<img src='{$this->ipsclass->vars['board_url']}/style_images/{$this->image_dir}/{$this->ipsclass->cache['attachtypes'][ $r['attach_ext'] ]['atype_img']}' border='0' />" ,
																				 "<a href='{$this->ipsclass->vars['board_url']}/index.php?act=attach&type=post&id={$r['attach_id']}' target='_blank'>{$r['attach_file']}</a>",
																				 $this->ipsclass->size_format($r['attach_filesize']),
																				 $r['members_display_name'],
																				 "<a href='{$this->ipsclass->vars['board_url']}/index.php?showtopic={$r['tid']}&view=findpost&p={$r['attach_rel_id']}' target='_blank' title='{$r['title']}'>{$r['stitle']}</a>",
																				 $this->ipsclass->get_date( $r['post_date'], 'SHORT', 1 ),
																				 "<div align='center'><input type='checkbox' name='attach_{$r['attach_id']}' value='1' /></div>",
																		)      );
		}
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		//-----------------------------------------
		// Most popular
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"    , "1%" );
		$this->ipsclass->adskin->td_header[] = array( "Attachment", "20%" );
		$this->ipsclass->adskin->td_header[] = array( "Viewed"    , "10%" );
		$this->ipsclass->adskin->td_header[] = array( "Author"    , "15%" );
		$this->ipsclass->adskin->td_header[] = array( "Topic"     , "25%" );
		$this->ipsclass->adskin->td_header[] = array( "Posted    ", "25%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;"    , "1%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Attachments: Top 5 Most Viewed" );
		
		$this->ipsclass->DB->build_query( array( 'select'   => 'a.*',
												 'from'     => array( 'attachments' => 'a' ),
												 'where'    => "attach_rel_module='post'",
												 'add_join' => array(
												 # POST TABLE JOIN
												 					  1 => array( 'select' => 'p.author_id, p.author_name, p.post_date',
												 					  			  'from'   => array( 'posts' => 'p' ),
												 					  			  'where'  => 'p.pid=a.attach_rel_id',
												 					  			  'type'   => 'left' ),
												 # TOPIC TABLE JOIN 					  			  
												 					  0 => array( 'select' => 't.tid, t.forum_id, t.title',
												 								  'from'   => array( 'topics' => 't' ),
												 								  'where'  => 'p.topic_id=t.tid',
												 								  'type'   => 'left' ),
												 # MEMBER TABLE JOIN
												 					  2 => array( 'select' => 'm.members_display_name',
												 					  			  'from'   => array( 'members' => 'm' ),
												 					  			  'where'  => 'm.id=a.attach_member_id',
												 					  			  'type'   => 'left' )
												 					 ),
												 'order'	=> "a.attach_hits DESC",
												 'limit'    => array( 0, 5 ) ) );
												 
		$this->ipsclass->DB->exec_query();
	
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$r['stitle'] = $this->ipsclass->txt_truncate($r['title'], 30);
			
			$size = $this->ipsclass->size_format($r['attach_filesize']);
			
			$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<img src='{$this->ipsclass->vars['board_url']}/style_images/{$this->image_dir}/{$this->ipsclass->cache['attachtypes'][ $r['attach_ext'] ]['atype_img']}' border='0' />" ,
																				 "<a title='{$size}' href='{$this->ipsclass->vars['board_url']}/index.php?act=attach&type=post&id={$r['attach_id']}' target='_blank'>{$r['attach_file']}</a>",
																				 $r['attach_hits'],
																				 $r['members_display_name'],
																				 "<a href='{$this->ipsclass->vars['board_url']}/index.php?showtopic={$r['tid']}&view=findpost&p={$r['attach_rel_id']}' target='_blank' title='{$r['title']}'>{$r['stitle']}</a>",
																				 $this->ipsclass->get_date( $r['post_date'], 'SHORT', 1 ),
																				 "<div align='center'><input type='checkbox' name='attach_{$r['attach_id']}' value='1' /></div>",
																		)      );
		}
		
		$removebutton = "<input type='submit' value='Delete Checked Attachments' class='realdarkbutton'></form>";
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_basic( $removebutton, "right", "tablesubheader");
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		//-----------------------------------------
		// PRINT
		//-----------------------------------------
		
		$this->ipsclass->admin->output();
	}
	
	
	
	//-----------------------------------------
	// TYPE: Import
	//-----------------------------------------
	
	function attach_type_import()
	{
		$content = $this->ipsclass->admin->import_xml( 'ipb_attachtypes.xml' );
		
		//-----------------------------------------
		// Got anything?
		//-----------------------------------------
		
		if ( ! $content )
		{
			$this->ipsclass->main_msg = "Upload failed, ipb_attachtypes.xml was either missing or empty";
			$this->attach_type_start();
		}
		
		//-----------------------------------------
		// Get xml mah-do-dah
		//-----------------------------------------
		
		require_once( KERNEL_PATH.'class_xml.php' );

		$xml = new class_xml();
		
		//-----------------------------------------
		// Unpack the datafile
		//-----------------------------------------
		
		$xml->xml_parse_document( $content );
		
		//-----------------------------------------
		// Get current attachment types
		//-----------------------------------------
		
		$types = array();
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'attachments_type', 'order' => "atype_extension" ) );
		$this->ipsclass->DB->simple_exec();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$types[ $r['atype_extension'] ] = 1;
		}
		
		//-----------------------------------------
		// pArse
		//-----------------------------------------
		
		foreach( $xml->xml_array['attachtypesexport']['attachtypesgroup']['attachtype'] as $entry )
		{
			$insert_array = array( 'atype_extension' => $entry['atype_extension']['VALUE'],
								   'atype_mimetype'  => $entry['atype_mimetype']['VALUE'],
								   'atype_post'      => $entry['atype_post']['VALUE'],
								   'atype_photo'     => $entry['atype_photo']['VALUE'],
								   'atype_img'       => $entry['atype_img']['VALUE']
								 );
			
			if ( $types[ $entry['atype_extension']['VALUE'] ] )
			{
				continue;
			}
			
			if ( $entry['atype_extension']['VALUE'] and $entry['atype_mimetype']['VALUE'] )
			{
				$this->ipsclass->DB->do_insert( 'attachments_type', $insert_array );
			}
		}
		
		$this->attach_type_rebuildcache();
                    
		$this->ipsclass->main_msg = "Attachment Types XML file import completed";
		
		$this->attach_type_start();
	
	}
	
	//-----------------------------------------
	//
	// TYPES: Export
	//
	//-----------------------------------------
	
	function attach_type_export()
	{
		//-----------------------------------------
		// Get xml mah-do-dah
		//-----------------------------------------
		
		require_once( KERNEL_PATH.'class_xml.php' );

		$xml = new class_xml();
		
		//-----------------------------------------
		// Start...
		//-----------------------------------------
		
		$xml->xml_set_root( 'attachtypesexport', array( 'exported' => time() ) );
		
		//-----------------------------------------
		// Get emo group
		//-----------------------------------------
		
		$xml->xml_add_group( 'attachtypesgroup' );
		
		$this->ipsclass->DB->simple_construct( array( 'select' => 'atype_extension,atype_mimetype,atype_post,atype_photo,atype_img',
									  'from'   => 'attachments_type',
									  'order'  => "atype_extension" ) );
		$this->ipsclass->DB->simple_exec();
		
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$content = array();
			
			foreach ( $r as $k => $v )
			{
				$content[] = $xml->xml_build_simple_tag( $k, $v );
			}
			
			$entry[] = $xml->xml_build_entry( 'attachtype', $content );
		}
		
		$xml->xml_add_entry_to_group( 'attachtypesgroup', $entry );
		
		$xml->xml_format_document();
		
		//-----------------------------------------
		// Send to browser.
		//-----------------------------------------
		
		$this->ipsclass->admin->show_download( $xml->xml_document, 'ipb_attachtypes.xml' );
	}
	
	//-----------------------------------------
	//
	// TYPES: DELETE
	//
	//-----------------------------------------
	
	function attach_type_delete()
	{
		$this->ipsclass->input['id'] = intval($this->ipsclass->input['id']);
		
		$this->ipsclass->DB->simple_exec_query( array( 'delete' => 'attachments_type', 'where' => 'atype_id='.$this->ipsclass->input['id'] ) );
		
		$this->attach_type_rebuildcache();
		
		$this->ipsclass->main_msg = "Attachment type deleted";
		
		$this->attach_type_start();
	}
	
	//-----------------------------------------
	//
	// TYPES: SAVE (edit / add)
	//
	//-----------------------------------------
	
	function attach_type_save( $type='add' )
	{
		$this->ipsclass->input['id'] = intval($this->ipsclass->input['id']);
		
		//-----------------------------------------
		// check basics
		//-----------------------------------------
		
		if ( ! $this->ipsclass->input['atype_extension'] or ! $this->ipsclass->input['atype_mimetype'] )
		{
			$this->ipsclass->main_msg = "You must enter at least an extension and mime-type before continuing.";
			$this->attach_type_form( $type );
		}
		
		$save_array = array( 'atype_extension' => str_replace( ".", "", $this->ipsclass->input['atype_extension'] ),
							 'atype_mimetype'  => $this->ipsclass->input['atype_mimetype'],
							 'atype_post'      => $this->ipsclass->input['atype_post'],
							 'atype_photo'     => $this->ipsclass->input['atype_photo'],
							 'atype_img'       => $this->ipsclass->input['atype_img']
						   );
		
		if ( $type == 'add' )
		{
			//-----------------------------------------
			// Check for existing..
			//-----------------------------------------
			
			$attach = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'attachments_type', 'where' => "atype_extension='".$save_array['atype_extension']."'" ) );
			
			if ( $attach['atype_id'] )
			{
				$this->ipsclass->main_msg = "The extension '{$save_array['atype_extension']}' already exists, please choose another extension.";
				$this->attach_type_form($type);
			}
			
			$this->ipsclass->DB->do_insert( 'attachments_type', $save_array );
			
			$this->ipsclass->main_msg = "Attachment type added";
			
		}
		else
		{
			$this->ipsclass->DB->do_update( 'attachments_type', $save_array, 'atype_id='.$this->ipsclass->input['id'] );
			
			$this->ipsclass->main_msg = "Attachment type edited";
		}
		
		$this->attach_type_rebuildcache();
		
		$this->attach_type_start();
		
	}
	
	//-----------------------------------------
	//
	// TYPES: FORM (edit / add)
	//
	//-----------------------------------------
	
	function attach_type_form( $type='add' )
	{
		$this->ipsclass->input['id']     = isset($this->ipsclass->input['id']) ? intval($this->ipsclass->input['id']) : 0;
		$this->ipsclass->input['baseon'] = isset($this->ipsclass->input['baseon']) ? intval($this->ipsclass->input['baseon']) : 0;
		
		$this->ipsclass->admin->nav[] = array( '', 'Add/Edit Attachment Type' );
		
		if ( $type == 'add' )
		{
			$code   = 'attach_doadd';
			$button = 'Add New Attachment Type';
			
			if ( $this->ipsclass->input['baseon'] )
			{
				$attach = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'attachments_type', 'where' => 'atype_id='.$this->ipsclass->input['baseon'] ) );
			}
			else
			{
				$attach = array( 'atype_extension' 	=> '',
								 'atype_mimetype'	=> '',
								 'atype_post'		=> '',
								 'atype_photo'		=> '',
								 'atype_img'		=> '' );
			}
			
			//-----------------------------------------
			// Generate 'base on'
			//-----------------------------------------
			
			$dd = "";
			
			$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'attachments_type', 'order' => 'atype_extension' ) );
			$this->ipsclass->DB->simple_exec();
		
			while( $r = $this->ipsclass->DB->fetch_row() )
			{
				$dd .= "<option value='{$r['atype_id']}'>Base on: {$r['atype_extension']}</option>\n";
			}
			
			$title = "
					  <div style='float:right;width:auto;padding-right:3px;'>
					  <form method='post' action='{$this->ipsclass->base_url}&{$this->ipsclass->form_code}&code=attach_add'>
					  <input type='hidden' name='_admin_auth_key' value='{$this->ipsclass->_admin_auth_key}' />
					  <select name='baseon' class='realbutton'>{$dd}</select> &nbsp;<input type='submit' value='Go' class='realdarkbutton' />
					  </form>
					  </div><div style='padding-bottom:5px'>{$button}</div>";
			
		}
		else
		{
			$code   = 'attach_doedit';
			$button = 'Edit Attachment Type';
			$title  = $button;
			$attach = $this->ipsclass->DB->simple_exec_query( array( 'select' => '*', 'from' => 'attachments_type', 'where' => 'atype_id='.$this->ipsclass->input['id'] ) );
		
			if ( ! $attach['atype_id'] )
			{
				$this->ipsclass->main_msg = "No ID was passed, please try again.";
				$this->attach_type_start();
			}
		}
		
		//-----------------------------------------
		// HEADER
		//-----------------------------------------
		
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;" , "40%" );
		$this->ipsclass->adskin->td_header[] = array( "&nbsp;" , "60%" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( $title );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , $code     ),
																			 2 => array( 'act'   , 'attach'  ),
																			 3 => array( 'id'    , $this->ipsclass->input['id'] ),
																			 4 => array( 'section', $this->ipsclass->section_code ),
																	)      );
		
		//-----------------------------------------
		// FORM
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Attachment File Extension</b><div style='color:gray'>This is the (usually) three character filename suffix.<br />You don't need to add the '.' before the extension</div>",
												 				 $this->ipsclass->adskin->form_simple_input( 'atype_extension', ( isset($_POST['atype_extension']) AND $_POST['atype_extension'] ) ? $_POST['atype_extension'] : $attach['atype_extension'], 10 ),
														)      );
														
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Attachment Mime-Type</b><div style='color:gray'>Unsure what the correct mime-type is?. <a href='http://www.utoronto.ca/webdocs/HTMLdocs/Book/Book-3ed/appb/mimetype.html' target='_blank'>Try looking here</a></div>",
												 				 $this->ipsclass->adskin->form_simple_input( 'atype_mimetype', ( isset($_POST['atype_mimetype']) AND $_POST['atype_mimetype'] ) ? $_POST['atype_mimetype'] : $attach['atype_mimetype'], 40 ),
														)      );
														
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Allow this attachment in posts?</b>",
												 				 $this->ipsclass->adskin->form_yes_no( 'atype_post', ( isset($_POST['atype_post']) AND $_POST['atype_post'] ) ? $_POST['atype_post'] : $attach['atype_post'] ),
														)      );
														
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Allow this attachment in avatars / personal photos?</b>",
												 				 $this->ipsclass->adskin->form_yes_no( 'atype_photo', ( isset($_POST['atype_photo']) AND $_POST['atype_photo'] ) ? $_POST['atype_photo'] : $attach['atype_photo'] ),
														)      );
														
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array( "<b>Attachment Mini-Image</b><div style='color:gray'>This is the little icon that represents the attachment type in a post.</div>",
												 				 $this->ipsclass->adskin->form_simple_input( 'atype_img', ( isset($_POST['atype_img']) AND $_POST['atype_img'] ) ? $_POST['atype_img'] : $attach['atype_img'], 40 ),
														)      );	
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form($button);
										 
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		//-----------------------------------------
		//-------------------------------
		
		$this->ipsclass->admin->output();
	}
	
	
	//-----------------------------------------
	//
	// TYPES: Start
	//
	//-----------------------------------------
	
	function attach_type_start()
	{
		//-----------------------------------------
		// Get 'em
		//-----------------------------------------
		
		$this->ipsclass->DB->simple_construct( array( 'select' => '*', 'from' => 'attachments_type', 'order' => 'atype_extension' ) );
		$this->ipsclass->DB->simple_exec();
		
		$attach_html = "";
		
		while( $r = $this->ipsclass->DB->fetch_row() )
		{
			$r['_imagedir'] = $this->image_dir;
			
			$checked_img    = "<img src='{$this->ipsclass->skin_acp_url}/images/acp_check.gif' border='0' alt='X' />";
			$r['apost_checked']  = $r['atype_post']  ? $checked_img : '&nbsp;';
			$r['aphoto_checked'] = $r['atype_photo'] ? $checked_img : '&nbsp;';
			
			$attach_html .= $this->html->attach_row( $r );
		}
		
		$this->ipsclass->html .= $this->html->attach_wrapper( $attach_html );
		
		//-----------------------------------------
		// IMPORT: Start output
		//-----------------------------------------
		
		$this->ipsclass->html .= $this->ipsclass->adskin->start_form( array( 1 => array( 'code'  , 'attach_import' ),
																			 2 => array( 'act'   , 'attach'        ),
																			 3 => array( 'MAX_FILE_SIZE', '10000000000' ),
																			 4 => array( 'section', $this->ipsclass->section_code ),
																	) , "uploadform", " enctype='multipart/form-data'"     );
													
													  			
		$this->ipsclass->html .= $this->ipsclass->adskin->start_table( "Import an Attachment Types List" );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->add_td_row( array(
													 		    "<b>Upload XML Attachment Types List</b><div style='color:gray'>Browse your computer for 'ipb_attachtypes.xml' or 'ipb_attachtypes.xml.gz'. Duplicate entries will not be imported.</div>",
													  		    $this->ipsclass->adskin->form_upload(  )
													    )      );
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_form("Import");
		
		$this->ipsclass->html .= $this->ipsclass->adskin->end_table();
		
		//-----------------------------------------
		
		$this->ipsclass->admin->output();
	}
	
	//-----------------------------------------
	//
	// TYPES: Rebuild Cache
	//
	//-----------------------------------------
	
	function attach_type_rebuildcache()
	{
		$this->ipsclass->cache['attachtypes'] = array();
			
		$this->ipsclass->DB->simple_construct( array( 'select' => 'atype_extension,atype_mimetype,atype_post,atype_photo,atype_img', 'from' => 'attachments_type', 'where' => "atype_photo=1 OR atype_post=1" ) );
		$this->ipsclass->DB->simple_exec();
	
		while ( $r = $this->ipsclass->DB->fetch_row() )
		{
			$this->ipsclass->cache['attachtypes'][ $r['atype_extension'] ] = $r;
		}
		
		$this->ipsclass->update_cache( array( 'name' => 'attachtypes', 'array' => 1, 'deletefirst' => 1 ) );
	}
	
	
	
	
	
	
	
}

?>