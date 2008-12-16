<?php

/*
+---------------------------------------------------------------------------
|   Invision Power Dynamic v1.0.0
|   ========================================
|   by Matthew Mecham
|   (c) 2004 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
+---------------------------------------------------------------------------
|   INVISION POWER DYNAMIC IS NOT FREE SOFTWARE!
|   http://www.invisionpower.com/dynamic/
+---------------------------------------------------------------------------
|   > $Id$
|   > $Revision: 4 $
|   > $Date: 2005-10-10 14:21:32 +0100 (Mon, 10 Oct 2005) $
+---------------------------------------------------------------------------
|
|   > POST PLUG-IN
|   > Script written by Matt Mecham
|   > Date started: Monday 19th December 2005 (11:29)
|
+---------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class plugin_post
{
	/**
	* Global ipsclass
	* @var	object
	*/
	var $ipsclass;
	
	/**
	* Module type
	* @var	string
	*/
	var $module = 'post';
	
	/*-------------------------------------------------------------------------*/
	// show_attachment_get_sql_data
	/*-------------------------------------------------------------------------*/
	/**
	* Checks the attachment and checks for download / show perms
	* Returns TRUE or FALSE
	* IT really does
	*
	* @return boolean
	*/
	function show_attachment_get_sql_data( $attach_id )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$_ok     = 0;
		
		if( !$attach_id )
		{
			return FALSE;
		}
		
		//-----------------------------------------
		// Grab 'em
		//-----------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select'   => 'a.*',
												 'from'     => array( 'attachments' => 'a' ),
												 'where'    => "a.attach_rel_module='".$this->module."' AND a.attach_id=".$attach_id,
												 'add_join' => array( 0 => array( 'select' => 'p.pid, p.topic_id, p.queued',
																				  'from'   => array( 'posts' => 'p' ),
																				  'where'  => "p.pid=a.attach_rel_id",
																				  'type'   => 'left' ),
															          1 => array( 'select' => 't.forum_id',
																				  'from'   => array( 'topics' => 't' ),
																				  'where'  => "t.tid=p.topic_id",
																				  'type'   => 'left' ) )
										)      );

		$attach_sql = $this->ipsclass->DB->exec_query();
		
		$attach     = $this->ipsclass->DB->fetch_row( $attach_sql );
		
		//-----------------------------------------
		// Check..
		//-----------------------------------------
		
		if ( ! isset( $attach['pid'] ) OR empty( $attach['pid'] ) )
		{
			if( $attach['attach_member_id'] != $this->ipsclass->member['id'] )
			{
				return FALSE;
			}
		}
		
		//-----------------------------------------
		// Queued post?
		//-----------------------------------------
		
		if ( $attach['queued'] )
		{ 
			if ( ! $this->ipsclass->can_queue_posts( $attach['forum_id'] ) )
			{
				return FALSE;
			}
		}
		
		//-----------------------------------------
    	// TheWalrus inspired fix for previewing
    	// the post and clicking the attachment...
    	//-----------------------------------------

    	if ( $attach['attach_rel_id'] == 0 AND $attach['attach_member_id'] == $this->ipsclass->member['id'] )
    	{
    		$_ok = 1;
    	}
    	else
    	{
			if ( ! $this->ipsclass->forums->forum_by_id[ $attach['forum_id'] ] )
			{
				//-----------------------------------------
				// TheWalrus inspired fix for previewing
				// the post and clicking the attachment...
				//-----------------------------------------
				
				if ( $attach['attach_rel_id'] == 0 AND $attach['attach_member_id'] == $this->ipsclass->member['id'] )
				{
					# We're ok.
				}
				else
				{
					return FALSE;
				}
			}

			if ( $this->ipsclass->check_perms($this->ipsclass->forums->forum_by_id[ $attach['forum_id'] ]['read_perms']) === FALSE )
			{
				return FALSE;
			}

	        if ( $this->ipsclass->check_perms($this->ipsclass->forums->forum_by_id[ $attach['forum_id'] ]['download_perms']) === FALSE )
	        {
				return FALSE;
			}
			
			//-----------------------------------------
			// Still here?
			//-----------------------------------------
			
			$_ok = 1;
		}

		//-----------------------------------------
		// Ok?
		//-----------------------------------------

		if ( $_ok )
		{
			return $attach;
		}
		else
		{
			return FALSE;
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// render_attachment_get_sql_data
	/*-------------------------------------------------------------------------*/
	/**
	* Check the attachment and make sure its OK to display
	* Returns TRUE or FALSE
	* IT really does
	*
	* @return boolean
	*/
	function render_attachment_get_sql_data( $attach_ids, $rel_ids=array(), $attach_post_key=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$rows  		= array();
		$query_bits	= array();
		$query 		= '';
		$match 		= 0;
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( is_array( $attach_ids ) AND count( $attach_ids ) )
		{
			$query_bits[] = "attach_id IN (" . implode( ",", $attach_ids ) .")";
		}
		
		if ( is_array( $rel_ids ) and count( $rel_ids ) )
		{
			$query_bits[] = "attach_rel_id IN (" . implode( ",", $rel_ids ) . ")";
			//$query = " OR attach_rel_id IN (-1," . implode( ",", $rel_ids ) . ")";
			$match = 1;
		}
		
		if ( $attach_post_key )
		{
			$query_bits[] = "attach_post_key='".$this->ipsclass->DB->add_slashes( $attach_post_key )."'";
			//$query .= " OR attach_post_key='".$this->ipsclass->DB->add_slashes( $attach_post_key )."'";
			$match  = 2;
		}
		
		if( !count($query_bits) )
		{
			$query = "attach_id IN (-1)";
		}
		else
		{
			$query = implode( " OR ", $query_bits );
		}
		
		//-----------------------------------------
		// Grab 'em
		//-----------------------------------------
		
		$this->ipsclass->DB->build_query( array( 'select'   => '*',
												 'from'     => 'attachments',
												 'where'    => "attach_rel_module='".$this->module."' AND ( " . $query . " )",
										)      );

		$attach_sql = $this->ipsclass->DB->exec_query();
		
		//-----------------------------------------
		// Loop through and filter off naughty ids
		//-----------------------------------------
		
		while( $db_row = $this->ipsclass->DB->fetch_row( $attach_sql ) )
		{
			$_ok = 1;
			
			if ( $match == 1 )
			{
				if ( ! in_array( $db_row['attach_rel_id'], $rel_ids ) )
				{
					$_ok = 0;
				}
			}
			else if ( $match == 2 )
			{
				if ( $db_row['attach_post_key'] != $attach_post_key )
				{
					$_ok = 0;
				}
			}
			
			//-----------------------------------------
			// Ok?
			//-----------------------------------------
			
			if ( $_ok )
			{
				$rows[ $db_row['attach_id'] ] = $db_row;
			}
		}
		
		//-----------------------------------------
		// Return
		//-----------------------------------------
	
		return $rows;
	}
	
	/*-------------------------------------------------------------------------*/
	// Remove attachment clean up
	/*-------------------------------------------------------------------------*/
	/**
	* Recounts number of attachments for the articles row
	*
	* @return boolean
	*/
	function post_process_upload( $post_key, $rel_id, $args=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$cnt = array( 'cnt' => 0 );
		
		//-----------------------------------------
		// Check..
		//-----------------------------------------
		
		if ( ! $post_key )
		{
			return 0;
		}
		
		
		$this->ipsclass->DB->simple_construct( array( "select" => 'COUNT(*) as cnt',
													  'from'   => 'attachments',
													  'where'  => "attach_post_key='{$post_key}'") );
		$this->ipsclass->DB->simple_exec();
	
		$cnt = $this->ipsclass->DB->fetch_row();
		
		if ( $cnt['cnt'] )
		{
			$this->ipsclass->DB->build_and_exec_query( array( 'update' => 'topics',
														  	  'set'    => "topic_hasattach=topic_hasattach+" . $cnt['cnt'],
														  	  'where'  => "tid=" . intval( $args['topic_id'] ) ) );
		}
		
		return array( 'count' => $cnt['cnt'] );
	}
	
	/*-------------------------------------------------------------------------*/
	// Remove attachment clean up
	/*-------------------------------------------------------------------------*/
	/**
	* Recounts number of attachments for the articles row
	*
	* @return boolean
	*/
	function remove_attachment_clean_up( $attachment )
	{
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! $attachment['attach_id'] )
		{
			return FALSE;
		}
		
		$this->ipsclass->DB->build_query( array( 'select'   => 'p.pid',
												 'from'     => array( 'posts' => 'p' ),
												 'where'    => 'p.pid='. intval( $attachment['attach_rel_id'] ),
												 'add_join' => array( 0 => array( 'select' => 't.forum_id, t.tid',
																				  'from'   => array( 'topics' => 't' ),
																				  'where'  => 't.tid=p.topic_id',
																				  'type'   => 'inner' ) ) ) );
																				
		$this->ipsclass->DB->exec_query();
		
		$topic = $this->ipsclass->DB->fetch_row();
	
		if ( isset( $topic['tid'] ) )
		{
			//-----------------------------------------
			// GET PIDS
			//-----------------------------------------
		
			$pids  = array();
			$count = 0;
		
			$this->ipsclass->DB->simple_construct( array( 'select' => 'pid',
														  'from'   => 'posts',
														  'where'  => "topic_id=". $topic['tid'] ) );
			$this->ipsclass->DB->simple_exec();
				
			while ( $p = $this->ipsclass->DB->fetch_row() )
			{
				$pids[] = $p['pid'];
			}
		
			//-----------------------------------------
			// GET ATTACHMENT COUNT
			//-----------------------------------------
		
			if ( count( $pids ) )
			{
				$this->ipsclass->DB->simple_construct( array( "select" => 'count(*) as cnt',
															  'from'   => 'attachments',
															  'where'  => "attach_rel_module='post' AND attach_rel_id IN(".implode(",",$pids).")") );
				$this->ipsclass->DB->simple_exec();
			
				$cnt = $this->ipsclass->DB->fetch_row();
			
				$count = intval( $cnt['cnt'] );
			}
		
			$this->ipsclass->DB->simple_construct( array( 'update' => 'topics', 'set' => "topic_hasattach=". $count , 'where' => "tid=".$topic['tid'] ) );
			$this->ipsclass->DB->simple_exec();
		}
		
		return TRUE;
	}
	
	/*-------------------------------------------------------------------------*/
	// Bulk remove attachment permissions check
	/*-------------------------------------------------------------------------*/
	/**
	* Returns TRUE or FALSE
	* IT really does
	*
	* @return boolean
	*/
	function return_bulk_remove_permission( $attach_rel_ids=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$ok_to_remove = FALSE;
		
		//-----------------------------------------
		// Allowed to remove?
		//-----------------------------------------
		
		if ( $this->ipsclass->member['group_access_cp'] )
		{
			$ok_to_remove = TRUE;
		}
		
		return $ok_to_remove;
	}
	
	/*-------------------------------------------------------------------------*/
	// Remove attachment permissions check
	/*-------------------------------------------------------------------------*/
	/**
	* Returns TRUE or FALSE
	* IT really does
	*
	* @return boolean
	*/
	function return_remove_permission( $attachment )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$ok_to_remove = FALSE;
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! $attachment['attach_id'] )
		{
			return FALSE;
		}
		
		//-----------------------------------------
		// Allowed to remove?
		//-----------------------------------------
		
		if ( $this->ipsclass->member['id'] == $attachment['attach_member_id'] )
		{
			$ok_to_remove = TRUE;
		}
		else if ( $this->ipsclass->member['g_is_supmod'] )
		{
			$ok_to_remove = TRUE;
		}
		else
		{
			//-----------------------------------------
			// Moderstor? Get forum ID
			//-----------------------------------------
			
			if ( isset( $this->ipsclass->member['_moderator'] ) )
			{
				$this->ipsclass->DB->build_query( array( 'select'   => 'p.pid',
														 'from'     => array( 'posts' => 'p' ),
														 'where'    => 'p.pid='. intval( $attachment['attach_rel_id'] ),
														 'add_join' => array( 0 => array( 'select' => 't.forum_id, t.tid',
																						  'from'   => array( 'topics' => 't' ),
																						  'where'  => 't.tid=p.topic_id',
																						  'type'   => 'inner' ) ) ) );
																						
				$this->ipsclass->DB->exec_query();
				
				$topic = $this->ipsclass->DB->fetch_row();
				
				if ( isset( $topic['forum_id'] ) )
				{
					if ( isset($this->ipsclass->member['_moderator'][ $topic['forum_id'] ]['edit_post']) and $this->ipsclass->member['_moderator'][ $topic['forum_id'] ]['edit_post'] )
					{
						$ok_to_remove = TRUE;
					}
				}
			}	
		}
		
		return $ok_to_remove;
	}
	
	/*-------------------------------------------------------------------------*/
	// Get allowed upload sizes
	/*-------------------------------------------------------------------------*/
	/**
	* get_space_allowance
	*
	* Returns an array of the allowed upload sizes in bytes.
	* Return 'space_allowed' as -1 to not allow uploads.
    * Return 'space_allowed' as 0 to allow unlimited uploads
    * Return 'max_single_upload' as 0 to not set a limit
	*
	* @param	id	    Member ID
	* @param	string  MD5 post key
	* @return	array [ 'space_used', 'space_left', 'space_allowed', 'max_single_upload' ]
	*/
	function get_space_allowance( $post_key='', $member_id='' )
	{
		$max_php_size      = intval( $this->ipsclass->math_get_post_max_size() );
		$member_id         = intval( $member_id ? $member_id : $this->ipsclass->member['id'] );
		$forum_id          = intval( $this->ipsclass->input['forum_id'] ? $this->ipsclass->input['forum_id'] : $this->ipsclass->input['f'] );
		$space_left        = 0;
		$space_used        = 0;
		$space_allowed     = 0;
		$max_single_upload = 0;
		$space_calculated  = 0;
		
		if ( $post_key )
		{
			//-----------------------------------------
			// Check to make sure we're not attempting
			// to upload to another's post...
			//-----------------------------------------

			if ( ! $this->ipsclass->member['g_is_supmod'] AND !$this->ipsclass->member['is_mod'] )
			{
				$post = $this->ipsclass->DB->build_and_exec_query( array( 'select' => '*',
																		  'from'   => 'posts',
																		  'where'  => "post_key='".$post_key."'" ) );
				
				if ( $post['post_key'] AND ( $post['author_id'] != $member_id ) )
				{
					$space_allowed    = -1;
					$space_calculated = 1;
				}
			}
		}
		
		//-----------------------------------------
		// Generate total space allowed
		//-----------------------------------------
		
		$total_space_allowed = ( $this->ipsclass->member['g_attach_per_post'] ? $this->ipsclass->member['g_attach_per_post'] : $this->ipsclass->member['g_attach_max'] ) * 1024;
		
		//-----------------------------------------
		// Allowed to attach?
		//-----------------------------------------
		
		if ( ! $member_id OR ! $forum_id )
		{
			$space_allowed = -1;
		}
		if ( $this->ipsclass->check_perms( $this->ipsclass->cache['forum_cache'][ $forum_id ]['upload_perms'] ) !== TRUE )
		{
			$space_allowed = -1;
		}
		else if ( ! $space_calculated )
		{
			//-----------------------------------------
			// Generate space allowed figure
			//-----------------------------------------
			
			if ( $this->ipsclass->member['g_attach_per_post'] )
			{
				//-----------------------------------------
				// Per post limit...
				//-----------------------------------------
				
				$_space_used = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'SUM(attach_filesize) as figure',
																				 'from'   => 'attachments',
																				 'where'  => "attach_post_key='".$post_key."'" ) );

				$space_used    = intval( $_space_used['figure'] );
			}
			else
			{
				//-----------------------------------------
				// Global limit...
				//-----------------------------------------
				
				$_space_used = $this->ipsclass->DB->build_and_exec_query( array( 'select' => 'SUM(attach_filesize) as figure',
																				 'from'   => 'attachments',
																				 'where'  => 'attach_member_id='.$member_id ) );

				$space_used    = intval( $_space_used['figure'] );
			}	

			if ( $this->ipsclass->member['g_attach_max'] > 0 )
			{
				if ( $this->ipsclass->member['g_attach_per_post'] )
				{
					$space_allowed = intval( ( $this->ipsclass->member['g_attach_per_post'] * 1024 ) - $space_used );
					$space_allowed = $space_allowed < 0 ? -1 : $space_allowed;
				}
				else
				{
					$space_allowed = intval( ( $this->ipsclass->member['g_attach_max'] * 1024 ) - $space_used );
					$space_allowed = $space_allowed < 0 ? -1 : $space_allowed;
				}
			}
			else
			{
				if ( $this->ipsclass->member['g_attach_per_post'] )
				{
					$space_allowed = intval( ( $this->ipsclass->member['g_attach_per_post'] * 1024 ) - $space_used );
					$space_allowed = $space_allowed < 0 ? -1 : $space_allowed;
				}
				else
				{
					# Unlimited
					$space_allowed = 0;
				}
			}
			
			//-----------------------------------------
			// Generate space left figure
			//-----------------------------------------
			
			$space_left = $space_allowed ? $space_allowed : 0;
			$space_left = ($space_left < 0) ? -1 : $space_left;
			
			//-----------------------------------------
			// Generate max upload size
			//-----------------------------------------
			
			if ( ! $max_single_upload )
			{
				if ( $space_left > 0 AND $space_left < $max_php_size )
				{
					$max_single_upload = $space_left;
				}
				else if ( $max_php_size )
				{
					$max_single_upload = $max_php_size;
				}
			}
		}
		
		$return = array( 'space_used' => $space_used, 'space_left' => $space_left, 'space_allowed' => $space_allowed, 'max_single_upload' => $max_single_upload, 'total_space_allowed' => $total_space_allowed );

		return $return;
	}
	
	/*-------------------------------------------------------------------------*/
	// Get settings
	/*-------------------------------------------------------------------------*/
	/**
	* get_settings
	*
	* Returns an array of settings:
	* 'siu_thumb'	= Allow thumbnail creation?
	* 'siu_height'  = Height of the generated thumbnail in pixels
	* 'siu_width'   = Width of the generated thumbnail in pixels
	* 'upload_dir'  = Base upload directory (must be a full path)
	*
	* You can omit any of these settings and IPB will use the default
	* settings (which are the ones entered into the ACP for post thumbnails)
	*
	* @return	boolean
	*/
	function get_settings()
	{
		$this->settings = array();
		
		return true;
	}

}

?>